<?php
/**
 * Finalize a paid order after gateway confirmation.
 */

require_once __DIR__ . '/../dal/OrderDAL.php';
require_once __DIR__ . '/../dal/CartDAL.php';
require_once __DIR__ . '/../dal/PointsDAL.php';
require_once __DIR__ . '/../dal/ProductDAL.php';
require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
require_once __DIR__ . '/../dal/ProductVariationDAL.php';
require_once __DIR__ . '/../services/AsyncMailQueue.php';
require_once __DIR__ . '/email.php';
require_once __DIR__ . '/sms.php';
require_once __DIR__ . '/invoice.php';

function ezymart_finalize_paid_order(int $orderId, ?int $userId = null): array
{
    $orderDAL = new OrderDAL();
    $order = $orderDAL->getById($orderId);

    if (!$order) {
        return ['success' => false, 'message' => 'Order not found'];
    }

    if ($userId !== null && (int)$order['user_id'] !== $userId) {
        return ['success' => false, 'message' => 'Unauthorized'];
    }

    if ($order['payment_status'] === 'paid') {
        return ['success' => true, 'message' => 'Already paid', 'order' => $order];
    }

    $orderDAL->updatePaymentStatus($orderId, 'paid');
    $orderDAL->updateOrderStatus($orderId, 'processing');

    $productDAL = new ProductDAL();
    $variationDAL = new ProductVariationDAL();
    $lowStockProducts = [];
    $lowStockThreshold = 5;

    try {
        $settingsDAL = new SiteSettingsDAL();
        $lowStockThreshold = (int)$settingsDAL->get('low_stock_threshold', '5');
    } catch (Exception $e) {
    }

    $orderItems = $orderDAL->getOrderItems($orderId);

    foreach ($orderItems as $item) {
        if (!empty($item['variation_id'])) {
            $variationDAL->decrementVariationStock((int)$item['variation_id'], (int)$item['quantity']);
        } else {
            $productDAL->decrementStock($item['product_id'], $item['quantity']);
        }

        $product = $productDAL->getById($item['product_id']);
        if ($product && $product['stock'] < $lowStockThreshold) {
            $lowStockProducts[] = $product;
        }
    }

    $pointsDAL = new PointsDAL();
    $pointsDAL->addPoints($order['user_id'], $order['points_earned'], 'Order #' . $order['order_number'], $orderId);

    $cartDAL = new CartDAL();
    $cartDAL->clearCart($order['user_id']);

    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION['applied_coupon'], $_SESSION['payment_order_id'], $_SESSION['payment_amount'], $_SESSION['payment_info'], $_SESSION['payment_gateway']);
        $_SESSION['last_order_id'] = $orderId;
        $_SESSION['last_order_number'] = $order['order_number'];
        $_SESSION['order_success_token'] = bin2hex(random_bytes(16));
    }

    $orderData = $order;
    $orderItemsCopy = $orderItems;
    $lowStockCopy = $lowStockProducts;

    if (!empty($lowStockCopy)) {
        AsyncMailQueue::add(static function () use ($lowStockCopy) {
            lumak_send_low_stock_alert($lowStockCopy);
            ezymart_sms_notify_admin_low_stock($lowStockCopy);
        });
    }

    AsyncMailQueue::add(static function () use ($orderData, $orderItemsCopy) {
        lumak_send_new_order_notification(
            $orderData['order_number'],
            $orderData['full_name'],
            $orderData['phone'],
            $orderData['email'],
            $orderData['total'],
            $orderItemsCopy,
            ['card_confirmed' => true]
        );
        ezymart_sms_notify_admin_new_order(
            $orderData['order_number'],
            $orderData['full_name'],
            (float)$orderData['total'],
            ['card_confirmed' => true]
        );
    });

    AsyncMailQueue::add(static function () use ($orderData) {
        ezymart_sms_notify_order_placed(
            $orderData['phone'],
            $orderData['order_number'],
            (float)$orderData['total']
        );
    });

    $orderIdCopy = $orderId;
    AsyncMailQueue::add(static function () use ($orderIdCopy) {
        lumak_invoice_try_auto_send($orderIdCopy, 'payment_confirmed');
    });

    return ['success' => true, 'message' => 'Payment confirmed', 'order' => $order];
}
