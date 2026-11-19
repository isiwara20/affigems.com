<?php
/**
 * Live chat — admin notifications and async response helpers.
 */

require_once __DIR__ . '/email.php';
require_once __DIR__ . '/../dal/ChatDAL.php';
require_once __DIR__ . '/../services/AsyncMailQueue.php';

function lumak_chat_admin_email(): string
{
    return defined('STORE_EMAIL') ? STORE_EMAIL : 'murshidmunzeer@gmail.com';
}

/**
 * @return array{name:string,email:string}
 */
function lumak_chat_customer_details(array $conversation): array
{
    $userName = trim((string)($conversation['guest_name'] ?? '')) ?: 'Guest';
    $userEmail = trim((string)($conversation['guest_email'] ?? ''));

    if (!empty($conversation['user_id'])) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare('SELECT name, email FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => (int)$conversation['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                $userName = trim((string)($user['name'] ?? '')) ?: $userName;
                $userEmail = trim((string)($user['email'] ?? '')) ?: $userEmail;
            }
        } catch (Throwable $e) {
            // Keep guest defaults
        }
    }

    return [
        'name' => $userName,
        'email' => $userEmail,
    ];
}

function lumak_send_admin_chat_notification(int $conversationId, ?int $messageId = null): bool
{
    $chatDAL = new ChatDAL();
    $conversation = $chatDAL->getConversation($conversationId);
    if (!$conversation) {
        return false;
    }

    $customer = lumak_chat_customer_details($conversation);
    $clientMessage = null;

    if ($messageId) {
        $message = $chatDAL->getMessage($messageId);
        if ($message && ($message['sender'] ?? '') === 'user') {
            $clientMessage = trim((string)($message['message'] ?? ''));
        }
    }

    $subject = $clientMessage
        ? 'New chat message from ' . $customer['name'] . ' | ' . lumak_email_brand_name()
        : 'New chat support request from ' . $customer['name'] . ' | ' . lumak_email_brand_name();

    $alt = $clientMessage
        ? "New chat message from {$customer['name']} ({$customer['email']}): {$clientMessage}. Log in to the admin dashboard to reply."
        : "New chat support request from {$customer['name']} ({$customer['email']}). Conversation #{$conversationId}.";

    return lumak_send_html_mail(
        lumak_chat_admin_email(),
        lumak_email_brand_name() . ' Support',
        $subject,
        lumak_email_chat_notification_template(
            $customer['name'],
            $customer['email'],
            $conversationId,
            $clientMessage
        ),
        $alt
    );
}

function lumak_queue_admin_chat_notification(int $conversationId, ?int $messageId = null): void
{
    $conversationIdCopy = $conversationId;
    $messageIdCopy = $messageId;

    AsyncMailQueue::add(static function () use ($conversationIdCopy, $messageIdCopy) {
        lumak_send_admin_chat_notification($conversationIdCopy, $messageIdCopy);
    });
}

/**
 * Send JSON to the chat client, then deliver queued notifications in the background.
 *
 * @param array<string,mixed> $payload
 * @param array<string,mixed>|null $detachJob
 */
function lumak_chat_finish_json_response(array $payload, ?array $detachJob = null): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        $json = '{"error":"Response encoding failed"}';
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

    AsyncMailQueue::flushAfterResponse($detachJob);
    exit;
}
