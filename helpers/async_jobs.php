<?php
/**
 * Serializable background jobs for AsyncMailQueue / workers/async-job.php
 */

require_once __DIR__ . '/contact.php';
require_once __DIR__ . '/order_notifications.php';
require_once __DIR__ . '/chat.php';

/**
 * @param array<string,mixed> $job
 */
function ezymart_async_run_job(array $job): void
{
    switch ($job['handler'] ?? '') {
        case 'contact_notify':
            $referenceNo = trim((string)($job['reference_no'] ?? ''));
            if ($referenceNo === '') {
                throw new InvalidArgumentException('contact_notify: missing reference_no');
            }
            if (!ezymart_contact_dispatch_by_reference($referenceNo)) {
                throw new RuntimeException('contact_notify: could not load message ' . $referenceNo);
            }
            break;

        case 'order_status_notify':
            $orderId = (int)($job['order_id'] ?? 0);
            $status = trim((string)($job['status'] ?? ''));
            if ($orderId <= 0 || $status === '') {
                throw new InvalidArgumentException('order_status_notify: missing order_id or status');
            }
            lumak_dispatch_order_status_notifications($orderId, $status);
            break;

        case 'chat_admin_notify':
            $conversationId = (int)($job['conversation_id'] ?? 0);
            if ($conversationId <= 0) {
                throw new InvalidArgumentException('chat_admin_notify: missing conversation_id');
            }
            $messageId = isset($job['message_id']) ? (int)$job['message_id'] : null;
            if (!lumak_send_admin_chat_notification($conversationId, $messageId ?: null)) {
                throw new RuntimeException('chat_admin_notify: could not send admin email');
            }
            break;

        default:
            throw new InvalidArgumentException('Unknown async job handler: ' . ($job['handler'] ?? ''));
    }
}
