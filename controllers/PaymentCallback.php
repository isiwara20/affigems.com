<?php
/**
 * Payment Callback Controller
 * Handles gateway return/notify callbacks and order finalization.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../helpers/payment.php';
require_once __DIR__ . '/../helpers/payment_finalize.php';
require_once __DIR__ . '/../dal/OrderDAL.php';
require_once __DIR__ . '/../services/payment/PaymentGatewayFactory.php';
require_once __DIR__ . '/../services/AsyncMailQueue.php';

$gatewayId = $_GET['gateway'] ?? $_POST['gateway'] ?? '';
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$requestData = array_merge($_GET, $_POST);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($requestData['action'] ?? '') === 'mark_paid') {
    header('Content-Type: application/json');

    $orderId = (int)($requestData['order_id'] ?? 0);
    $userId = (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) ? (int)$_SESSION['user_id'] : null;

    if ($orderId > 0 && $userId) {
        $result = ezymart_finalize_paid_order($orderId, $userId);
        if (!empty($result['success'])) {
            AsyncMailQueue::sendResponse(['success' => true, 'message' => $result['message']]);
            AsyncMailQueue::flushAfterResponse();
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if ($gatewayId !== '') {
    $gateway = PaymentGatewayFactory::make($gatewayId);
    if (!$gateway) {
        header('Location: ' . BASE_URL . 'checkout.php?payment_failed=1');
        exit;
    }

    $orderDAL = new OrderDAL();
    $orderNumber = (string)($requestData['order_id'] ?? $requestData['orderId'] ?? '');

    if ($action === 'notify') {
        if ($gateway->verifyNotify($requestData) && $orderNumber !== '') {
            $order = $orderDAL->getByOrderNumber($orderNumber);
            if ($order) {
                ezymart_finalize_paid_order((int)$order['id']);
            }
        }
        AsyncMailQueue::flush();
        http_response_code(200);
        echo 'OK';
        exit;
    }

    if ($action === 'cancel') {
        header('Location: ' . BASE_URL . 'checkout.php?payment_failed=1');
        exit;
    }

    if ($action === 'return') {
        $verified = $gateway->verifyReturn($requestData);
        if (!$verified && $orderNumber !== '') {
            $order = $orderDAL->getByOrderNumber($orderNumber);
            if ($order && $order['payment_status'] === 'paid') {
                $verified = true;
            }
        }

        if ($verified && $orderNumber !== '') {
            $order = $orderDAL->getByOrderNumber($orderNumber);
            if ($order) {
                $userId = (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) ? (int)$_SESSION['user_id'] : null;
                ezymart_finalize_paid_order((int)$order['id'], $userId);
            }
            header('Location: ' . BASE_URL . 'order-success.php');
            AsyncMailQueue::flushAfterRedirect();
            exit;
        }

        header('Location: ' . BASE_URL . 'checkout.php?payment_failed=1');
        exit;
    }
}

// Legacy DirectPay response callback (GET redirect from DirectPay)
$status = $_GET['status'] ?? $_POST['status'] ?? '';
$legacyOrderId = $_GET['order_id'] ?? $_POST['order_id'] ?? '';
if ($status !== '' && $legacyOrderId !== '') {
    $orderDAL = new OrderDAL();
    $order = $orderDAL->getByOrderNumber((string)$legacyOrderId);

    if (strtoupper($status) === 'SUCCESS' && $order) {
        $userId = (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) ? (int)$_SESSION['user_id'] : null;
        ezymart_finalize_paid_order((int)$order['id'], $userId);
        header('Location: ' . BASE_URL . 'order-success.php');
        AsyncMailQueue::flushAfterRedirect();
    } else {
        header('Location: ' . BASE_URL . 'checkout.php?payment_failed=1');
    }
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'No action specified']);
