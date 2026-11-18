<?php
/**
 * Payment page — gateway selection, then gateway checkout.
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/helpers/payment.php';
require_once __DIR__ . '/dal/OrderDAL.php';
require_once __DIR__ . '/services/payment/PaymentGatewayFactory.php';

if (!isset($_SESSION['payment_order_id']) || !isset($_SESSION['payment_base_amount'])) {
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

$orderId = (int)$_SESSION['payment_order_id'];
$baseAmount = (float)$_SESSION['payment_base_amount'];
$paymentInfo = $_SESSION['payment_info'] ?? [];
$orderNumber = $_SESSION['last_order_number'] ?? 'ORDER' . time();
$orderDAL = new OrderDAL();

if (isset($_GET['change_gateway'])) {
    unset($_SESSION['payment_gateway'], $_SESSION['payment_surcharge']);
    $_SESSION['payment_amount'] = $baseAmount;
    $orderDAL->applyPaymentGatewaySurcharge($orderId, null, 0, $baseAmount);
    header('Location: ' . BASE_URL . 'payment.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_gateway'])) {
    $gatewayId = trim($_POST['select_gateway'] ?? '');
    $available = ezymart_payment_available_gateways();

    if (!in_array($gatewayId, $available, true)) {
        $_SESSION['checkout_error'] = 'Please select a valid payment method.';
        header('Location: ' . BASE_URL . 'payment.php');
        exit;
    }

    $surcharge = ezymart_payment_calculate_surcharge($baseAmount, $gatewayId);
    $finalAmount = ezymart_payment_total_with_surcharge($baseAmount, $gatewayId);

    $orderDAL->applyPaymentGatewaySurcharge($orderId, $gatewayId, $surcharge, $finalAmount);

    $_SESSION['payment_gateway'] = $gatewayId;
    $_SESSION['payment_surcharge'] = $surcharge;
    $_SESSION['payment_amount'] = $finalAmount;

    header('Location: ' . BASE_URL . 'payment.php');
    exit;
}

$gatewayId = $_SESSION['payment_gateway'] ?? null;

if ($gatewayId === null) {
    $availableGateways = ezymart_payment_available_gateways();
    if (empty($availableGateways)) {
        $_SESSION['checkout_error'] = 'No online payment methods are available right now.';
        header('Location: ' . BASE_URL . 'checkout.php');
        exit;
    }

    $gatewayLabels = ezymart_payment_gateway_labels();
    $gatewayDescriptions = ezymart_payment_gateway_descriptions();
    require __DIR__ . '/views/payment/select-gateway.php';
    exit;
}

$finalAmount = (float)($_SESSION['payment_amount'] ?? $baseAmount);
$surcharge = (float)($_SESSION['payment_surcharge'] ?? 0);
$gateway = PaymentGatewayFactory::make($gatewayId);

if (!$gateway || !$gateway->isConfigured()) {
    unset($_SESSION['payment_gateway'], $_SESSION['payment_surcharge']);
    $_SESSION['payment_amount'] = $baseAmount;
    $_SESSION['checkout_error'] = 'Selected payment method is not available. Please choose another.';
    header('Location: ' . BASE_URL . 'payment.php');
    exit;
}

$checkoutContext = $gateway->buildCheckoutContext([
    'order_id' => $orderId,
    'order_number' => $orderNumber,
    'amount' => $finalAmount,
    'payment_info' => $paymentInfo,
    'address' => $_SESSION['payment_address'] ?? [],
]);

if (($checkoutContext['mode'] ?? '') === 'redirect' && !empty($checkoutContext['redirect_url'])) {
    header('Location: ' . $checkoutContext['redirect_url']);
    exit;
}

$gatewayView = __DIR__ . '/views/payment/gateways/' . $gatewayId . '.php';
if (!is_file($gatewayView)) {
    $gatewayView = __DIR__ . '/views/payment/gateways/generic.php';
}

$pageTitle = 'Secure Payment - ' . $gateway->getLabel();
require __DIR__ . '/views/payment/layout.php';
