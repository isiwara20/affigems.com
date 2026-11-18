<?php
/**
 * Customer invoice PDF download (secure link from email/SMS).
 */
require_once __DIR__ . '/config/app.php';

$orderId = (int)($_GET['order_id'] ?? 0);
$token = trim((string)($_GET['token'] ?? ''));

if ($orderId <= 0 || $token === '') {
    http_response_code(400);
    exit('Invalid invoice link.');
}

$orderDAL = new OrderDAL();
$order = $orderDAL->getById($orderId);
if (!$order) {
    http_response_code(404);
    exit('Invoice not found.');
}

if (!lumak_invoice_verify_customer_token($orderId, (string)($order['order_number'] ?? ''), $token)) {
    http_response_code(403);
    exit('Invalid or expired invoice link.');
}

$items = lumak_invoice_enrich_items($orderId, $orderDAL->getOrderItems($orderId));
$settings = lumak_invoice_get_settings();

try {
    $pdf = lumak_invoice_generate_pdf($order, $items, $settings);
} catch (Throwable $e) {
    try {
        $pdf = lumak_invoice_generate_pdf_fallback($order, $items, $settings);
    } catch (Throwable $e2) {
        http_response_code(500);
        exit('Could not generate invoice PDF.');
    }
}

$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', lumak_invoice_number($order, $settings)) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, no-store');
echo $pdf;
exit;
