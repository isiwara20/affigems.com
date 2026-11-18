<?php
/**
 * Invoice HTML preview (admin).
 */
require_once __DIR__ . '/../config/app.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    exit('Unauthorized');
}

$orderId = (int)($_GET['order_id'] ?? 0);
$settings = lumak_invoice_get_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = array_merge($settings, $_POST);
}

$previewPaid = !empty($_POST['preview_paid']) || !empty($_GET['preview_paid']);

if ($orderId > 0) {
    $orderDAL = new OrderDAL();
    $order = $orderDAL->getById($orderId);
    $items = $order ? lumak_invoice_enrich_items($orderId, $orderDAL->getOrderItems($orderId)) : [];
    if (!$order) {
        $order = lumak_invoice_sample_order();
        $items = lumak_invoice_sample_items();
    }
} else {
    $order = lumak_invoice_sample_order();
    $items = lumak_invoice_sample_items();
}

if ($previewPaid) {
    $order['payment_status'] = 'paid';
}

header('Content-Type: text/html; charset=UTF-8');
echo lumak_invoice_render_html($order, $items, $settings);
