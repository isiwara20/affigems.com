<?php
/**
 * Order status change notifications (email + SMS).
 */

require_once __DIR__ . '/../dal/OrderDAL.php';
require_once __DIR__ . '/../services/MailService.php';
require_once __DIR__ . '/sms.php';

/**
 * @return array{success:bool,message:string,order_id?:int,status?:string}
 */
function lumak_apply_order_status_change(
    OrderDAL $orderDAL,
    int $orderId,
    string $newStatus,
    ?string $trackingId = null,
    ?string $courierCompany = null,
    bool $queueNotifications = false
): array {
    if (!in_array($newStatus, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
        return ['success' => false, 'message' => 'Invalid order status.'];
    }

    $order = $orderDAL->getById($orderId);
    if (!$order) {
        return ['success' => false, 'message' => 'Order not found.'];
    }

    $oldStatus = (string)($order['order_status'] ?? '');
    if ($oldStatus === $newStatus) {
        return ['success' => true, 'message' => 'Order status is already ' . $newStatus . '.'];
    }

    if ($newStatus === 'shipped') {
        $trackingId = trim((string)$trackingId);
        $courierCompany = trim((string)$courierCompany);
        if ($trackingId === '' || $courierCompany === '') {
            return [
                'success' => false,
                'message' => 'Tracking ID and courier company are required when marking an order as shipped.',
            ];
        }
        $orderDAL->updateShippingTracking($orderId, $trackingId, $courierCompany);
    }

    $orderDAL->updateOrderStatus($orderId, $newStatus);

    if ($queueNotifications) {
        lumak_queue_order_status_notifications($orderId, $newStatus);
    } else {
        lumak_dispatch_order_status_notifications($orderId, $newStatus);
    }

    $message = $newStatus === 'shipped'
        ? 'Order marked as shipped. Customer will be notified shortly.'
        : 'Order status updated!';

    return [
        'success' => true,
        'message' => $message,
        'order_id' => $orderId,
        'status' => $newStatus,
    ];
}

function lumak_dispatch_order_status_notifications(int $orderId, string $newStatus): void
{
    $orderDAL = new OrderDAL();
    $order = $orderDAL->getById($orderId);
    if (!$order) {
        return;
    }

    lumak_send_order_status_notifications($order, $newStatus);
    lumak_invoice_try_auto_send($orderId, $newStatus);
}

function lumak_queue_order_status_notifications(int $orderId, string $newStatus): void
{
    require_once __DIR__ . '/../services/AsyncMailQueue.php';

    $orderIdCopy = $orderId;
    $statusCopy = $newStatus;

    AsyncMailQueue::add(static function () use ($orderIdCopy, $statusCopy) {
        lumak_dispatch_order_status_notifications($orderIdCopy, $statusCopy);
    });
}

function lumak_send_order_status_notifications(array $order, string $newStatus): void
{
    $trackingId = trim((string)($order['tracking_id'] ?? ''));
    $courierCompany = trim((string)($order['courier_company'] ?? ''));

    if (!empty($order['email'])) {
        $mailService = new MailService();
        $mailService->sendOrderStatusEmail(
            $order['email'],
            $order['full_name'],
            $order['order_number'],
            $newStatus,
            $trackingId !== '' ? $trackingId : null,
            $courierCompany !== '' ? $courierCompany : null
        );
    }

    if (!empty($order['phone'])) {
        ezymart_sms_notify_order_status(
            $order['phone'],
            $order['order_number'],
            $newStatus,
            $trackingId !== '' ? $trackingId : null,
            $courierCompany !== '' ? $courierCompany : null
        );
    }
}

function lumak_admin_wants_json_response(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function lumak_admin_json_response(array $payload): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function lumak_admin_finish_json_request(array $payload): void
{
    require_once __DIR__ . '/../services/AsyncMailQueue.php';

    AsyncMailQueue::sendResponse($payload);

    $detachJob = null;
    if (!empty($payload['success']) && !empty($payload['order_id']) && !empty($payload['status'])) {
        $detachJob = [
            'handler' => 'order_status_notify',
            'order_id' => (int)$payload['order_id'],
            'status' => (string)$payload['status'],
        ];
    }

    AsyncMailQueue::flushAfterResponse($detachJob);
    exit;
}
