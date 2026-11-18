<?php
/**
 * Payment Query Controller
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/payment.php';
require_once __DIR__ . '/../services/payment/PaymentGatewayFactory.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'ERROR', 'message' => 'Invalid request']);
    exit;
}

$orderId = (string)($_POST['order_id'] ?? '');
$gatewayId = (string)($_POST['gateway'] ?? $_SESSION['payment_gateway'] ?? ezymart_payment_resolve_gateway('card'));

if ($orderId === '' || $gatewayId === '') {
    echo json_encode(['status' => 'ERROR', 'message' => 'Missing order or gateway']);
    exit;
}

$gateway = PaymentGatewayFactory::make($gatewayId);
if (!$gateway) {
    echo json_encode(['status' => 'ERROR', 'message' => 'Unknown gateway']);
    exit;
}

$status = $gateway->queryStatus($orderId);
echo json_encode(['status' => $status, 'gateway' => $gatewayId]);
