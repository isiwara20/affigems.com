<?php
/**
 * Send invoice email to customer (admin).
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
if ($orderId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid order']);
    exit;
}

$orderDAL = new OrderDAL();
$order = $orderDAL->getById($orderId);
if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}
if (empty($order['email'])) {
    echo json_encode(['success' => false, 'error' => 'Customer has no email address']);
    exit;
}

$sent = lumak_invoice_send_for_order($orderId, 'manual');
if ($sent) {
    $message = 'Invoice emailed to ' . $order['email'];
    if (!empty($order['phone']) && ezymart_sms_is_configured()) {
        $message .= ' and invoice link sent via SMS to ' . $order['phone'];
    }
    echo json_encode(['success' => true, 'message' => $message]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send invoice email']);
}
