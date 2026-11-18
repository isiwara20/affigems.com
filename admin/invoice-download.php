<?php
/**
 * Download invoice PDF (admin).
 */
require_once __DIR__ . '/../config/app.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    exit('Unauthorized');
}

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    http_response_code(400);
    exit('Invalid order');
}

$orderDAL = new OrderDAL();
$order = $orderDAL->getById($orderId);
if (!$order) {
    http_response_code(404);
    exit('Order not found');
}

$items = lumak_invoice_enrich_items($orderId, $orderDAL->getOrderItems($orderId));
$settings = lumak_invoice_get_settings();
$pdf = lumak_invoice_generate_pdf($order, $items, $settings);
$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', lumak_invoice_number($order, $settings)) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit;
