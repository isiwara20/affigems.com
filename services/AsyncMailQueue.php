<?php
/**
 * Async Mail Queue Service
 * 
 * Sends emails AFTER the HTTP response has been sent to the client.
 * This eliminates page buffering/delay when sending emails via SMTP.
 * 
 * Usage:
 *   AsyncMailQueue::add(function() { ... send email ... });
 *   AsyncMailQueue::flush(); // Call at the very end of the script
 * 
 * For AJAX endpoints: call AsyncMailQueue::sendResponse($data) then flush()
 * For redirect endpoints: call AsyncMailQueue::flushAfterRedirect()
 */

class AsyncMailQueue {
    private static $queue = [];

    /**
     * Add an email sending closure to the queue
     */
    public static function add(callable $task) {
        self::$queue[] = $task;
    }

    /**
     * Send JSON response immediately, then process queued emails
     * Use this in AJAX controllers (SubscribeController, ChatController, etc.)
     */
    public static function sendResponse($data) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Discard gzip/compression buffers from config/app.php — plain JSON only
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{"success":false,"error":"Response encoding failed"}';
        }

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Connection: close');
            header('Content-Length: ' . strlen($json));
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }

        echo $json;

        if (function_exists('flush')) {
            flush();
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * Process queued tasks after the client has received the HTTP response.
     * On PHP-FPM, runs queued closures after the connection closes.
     * On Linux Apache/mod_php, may spawn a detached CLI worker when PHP CLI is available.
     *
     * @param array<string,mixed>|null $detachJob Optional serializable job for background worker
     */
    public static function flushAfterResponse(?array $detachJob = null): void {
        if (empty(self::$queue) && $detachJob === null) {
            return;
        }

        // Only try detached workers on Linux servers with a working PHP CLI.
        if (!function_exists('fastcgi_finish_request') && self::canSpawnDetachedWorker()) {
            if ($detachJob !== null && self::spawnDetachedJob($detachJob)) {
                self::$queue = [];
                return;
            }
        }

        if (empty(self::$queue) && $detachJob !== null) {
            self::runDetachedJob($detachJob);
            return;
        }

        self::flush();
    }

    private static function canSpawnDetachedWorker(): bool {
        return strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN';
    }

    /**
     * @param array<string,mixed> $job
     */
    public static function runDetachedJob(array $job): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        ignore_user_abort(true);
        set_time_limit(120);

        try {
            require_once dirname(__DIR__) . '/helpers/async_jobs.php';
            ezymart_async_run_job($job);
        } catch (\Throwable $e) {
            error_log('EZYMART async job error: ' . $e->getMessage());
        }
    }

    private static function resolvePhpBinary(): string {
        if (PHP_SAPI === 'cli' && defined('PHP_BINARY') && PHP_BINARY !== '') {
            $bin = PHP_BINARY;
            if (stripos($bin, 'fpm') === false) {
                return $bin;
            }
        }

        if (defined('PHP_BINDIR') && PHP_BINDIR !== '') {
            $candidate = PHP_BINDIR . DIRECTORY_SEPARATOR . 'php';
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return 'php';
    }

    private static function isPhpCliAvailable(string $php): bool {
        if ($php !== 'php' && is_file($php)) {
            return true;
        }

        if (!function_exists('exec')) {
            return false;
        }

        $output = [];
        $exitCode = 1;
        @exec(escapeshellarg($php) . ' -v 2>&1', $output, $exitCode);

        return $exitCode === 0;
    }

    /**
     * @param array<string,mixed> $job
     */
    private static function spawnDetachedJob(array $job): bool {
        if (!self::canSpawnDetachedWorker()) {
            return false;
        }

        if (!function_exists('exec')) {
            return false;
        }

        $jobDir = dirname(__DIR__) . '/storage/async-jobs';
        if (!is_dir($jobDir)) {
            @mkdir($jobDir, 0755, true);
        }
        if (!is_dir($jobDir) || !is_writable($jobDir)) {
            $jobDir = sys_get_temp_dir();
        }

        $jobFile = rtrim($jobDir, '/\\') . DIRECTORY_SEPARATOR . 'job_' . bin2hex(random_bytes(8)) . '.json';
        if (@file_put_contents($jobFile, json_encode($job, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false) {
            return false;
        }

        $script = dirname(__DIR__) . '/workers/async-job.php';
        if (!is_file($script)) {
            @unlink($jobFile);
            return false;
        }

        $php = self::resolvePhpBinary();
        if (!self::isPhpCliAvailable($php)) {
            @unlink($jobFile);
            return false;
        }

        $cmd = escapeshellarg($php) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($jobFile);
        exec($cmd . ' > /dev/null 2>&1 &');

        return true;
    }

    /**
     * For redirect-based controllers (OrderController, AuthController)
     * Call this AFTER header('Location: ...') but BEFORE exit.
     * It closes the connection, lets the redirect happen, then sends emails.
     */
    public static function flushAfterRedirect() {
        if (empty(self::$queue)) return;

        // Keep running after the browser follows the redirect (Apache/mod_php).
        ignore_user_abort(true);
        set_time_limit(120);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (function_exists('flush')) {
            flush();
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        foreach (self::$queue as $task) {
            try {
                $task();
            } catch (\Throwable $e) {
                error_log("AsyncMail error: " . $e->getMessage());
            }
        }

        self::$queue = [];
    }

    /**
     * Flush queue after AJAX response has been sent
     */
    public static function flush() {
        if (empty(self::$queue)) return;

        // Close session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        ignore_user_abort(true);
        set_time_limit(120);

        foreach (self::$queue as $task) {
            try {
                $task();
            } catch (\Throwable $e) {
                error_log("AsyncMail error: " . $e->getMessage());
            }
        }

        self::$queue = [];
    }
}
