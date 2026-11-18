<?php
/**
 * Detached background worker for email/SMS jobs.
 * Usage: php workers/async-job.php /path/to/job.json
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$jobFile = $argv[1] ?? '';
if ($jobFile === '' || !is_file($jobFile)) {
    exit(1);
}

$job = json_decode((string)file_get_contents($jobFile), true);
@unlink($jobFile);

if (!is_array($job) || empty($job['handler'])) {
    exit(1);
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/async_jobs.php';

ignore_user_abort(true);
set_time_limit(120);

try {
    ezymart_async_run_job($job);
} catch (Throwable $e) {
    error_log('EZYMART async job error: ' . $e->getMessage());
    exit(1);
}
