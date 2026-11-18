<?php
/**
 * Order Controller
 * Handles order placement from checkout form
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/OrderDAL.php';
require_once __DIR__ . '/../dal/CartDAL.php';
require_once __DIR__ . '/../dal/CouponDAL.php';
require_once __DIR__ . '/../dal/PointsDAL.php';
require_once __DIR__ . '/../helpers/payment.php';
require_once __DIR__ . '/../services/AsyncMailQueue.php';

// Must be logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}

$userId = $_SESSION['user_id'];

try {

// Get cart items
$cartDAL = new CartDAL();
$cartItems = $cartDAL->getByUser($userId);

if (empty($cartItems)) {
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Coupon discount
$discount = 0;
$couponCode = $_SESSION['applied_coupon'] ?? '';
if (!empty($couponCode)) {
    $couponDAL = new CouponDAL();
    $result = $couponDAL->validateCoupon($couponCode, $subtotal);
    if ($result['valid']) {
        $discount = $result['discount'];
        // Increment coupon usage
        $couponDAL->incrementUsage($result['coupon']['id']);
    }
}

// Delivery fee from country
require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
require_once __DIR__ . '/../dal/ShippingRatesDAL.php';
$settingsDAL = new SiteSettingsDAL();
$shippingDAL = new ShippingRatesDAL();
$shippingCountry = strtoupper(trim($_POST['shipping_country'] ?? ShippingRatesDAL::SRI_LANKA_CODE));

// Generate order number
$orderNumber = 'AB' . date('ymd') . strtoupper(substr(uniqid(), -4));

// Get form data
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$district = trim($_POST['district'] ?? '');
$city = trim($_POST['city'] ?? '');
$postalCode = trim($_POST['postal_code'] ?? '');
$address1 = trim($_POST['address_1'] ?? '');
$address2 = trim($_POST['address_2'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$deliveryMethod = $_POST['delivery_method'] ?? 'standard';
$paymentMethod = $_POST['payment_method'] ?? 'cod';
$savedAddressId = $_POST['saved_address_id'] ?? 'new';

// If using a saved address, load it (Sri Lanka addresses)
if ($savedAddressId !== 'new' && !empty($savedAddressId)) {
    require_once __DIR__ . '/../dal/AddressDAL.php';
    $addressDAL = new AddressDAL();
    $savedAddr = $addressDAL->getById((int)$savedAddressId, $userId);
    if ($savedAddr) {
        $shippingCountry = ShippingRatesDAL::SRI_LANKA_CODE;
        $district = $savedAddr['district'] ?? '';
        $city = $savedAddr['city'] ?? '';
        $postalCode = $savedAddr['postal_code'] ?? '';
        $address1 = $savedAddr['address'];
        $address2 = '';
        if (empty($phone) && !empty($savedAddr['phone'])) {
            $phone = $savedAddr['phone'];
        }
    }
} else {
    // New address entered - always save to address book
    if (!empty($address1)) {
        try {
            require_once __DIR__ . '/../dal/AddressDAL.php';
            $addressDAL = new AddressDAL();
            $addressDAL->create($userId, [
                'label' => 'Home',
                'full_name' => $fullName,
                'phone' => $phone,
                'address' => $address1 . (!empty($address2) ? ', ' . $address2 : ''),
                'city' => $city,
                'district' => $district,
                'postal_code' => $postalCode,
                'is_default' => 0
            ]);
        } catch (Exception $e) {
            // Don't block order if address save fails
        }
    }
}

// Recalculate delivery after address/country resolution
$deliveryFee = $shippingDAL->calculateFee($shippingCountry, $subtotal);
$total = $subtotal - $discount + $deliveryFee;
$pointsToEarn = PointsDAL::calculatePoints($total);

// Validate
if (empty($fullName) || empty($phone) || empty($district) || empty($city) || empty($address1)) {
    $_SESSION['checkout_error'] = 'Please fill all required fields.';
    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}

// Create order
$orderDAL = new OrderDAL();
$paymentStatus = ($paymentMethod === 'cod') ? 'pending' : 'pending';

$orderId = $orderDAL->createOrder([
    ':order_number' => $orderNumber,
    ':user_id' => $userId,
    ':full_name' => $fullName,
    ':email' => $email,
    ':phone' => $phone,
    ':shipping_country' => $shippingCountry,
    ':district' => $district,
    ':city' => $city,
    ':postal_code' => $postalCode,
    ':address_1' => $address1,
    ':address_2' => $address2,
    ':notes' => $notes,
    ':subtotal' => $subtotal,
    ':discount' => $discount,
    ':delivery_fee' => $deliveryFee,
    ':total' => $total,
    ':coupon_code' => $couponCode ?: null,
    ':delivery_method' => $deliveryMethod,
    ':payment_method' => $paymentMethod,
    ':payment_status' => $paymentStatus,
    ':order_status' => 'pending',
    ':points_earned' => $pointsToEarn
]);

// Add order items
foreach ($cartItems as $item) {
    $orderDAL->addOrderItem(
        $orderId,
        $item['product_id'],
        $item['name'],
        $item['price'],
        $item['quantity'],
        !empty($item['variation_id']) ? (int)$item['variation_id'] : null,
        $item['variation_label'] ?? null
    );
}

// Store order info in session for payment/success pages
$_SESSION['last_order_id'] = $orderId;
$_SESSION['last_order_number'] = $orderNumber;

// Card / online payment — redirect to gateway selection (no stock/emails yet)
if ($paymentMethod === 'card') {
    $_SESSION['payment_order_id'] = $orderId;
    $_SESSION['payment_base_amount'] = $total;
    $_SESSION['payment_amount'] = $total;
    unset($_SESSION['payment_gateway'], $_SESSION['payment_surcharge']);
    $_SESSION['payment_info'] = [
        'fname' => explode(' ', $fullName)[0],
        'lname' => explode(' ', $fullName, 2)[1] ?? '',
        'email' => $email,
        'phone' => $phone
    ];
    $_SESSION['payment_address'] = [
        'line1' => $address1,
        'city' => $city,
        'district' => $district,
    ];
    header('Location: ' . BASE_URL . 'payment.php');
    exit;
}

// For COD orders: process stock, send emails, award points immediately
require_once __DIR__ . '/../dal/ProductDAL.php';
require_once __DIR__ . '/../dal/ProductVariationDAL.php';
require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
$productDAL = new ProductDAL();
$variationDAL = new ProductVariationDAL();
$lowStockProducts = [];
$lowStockThreshold = 5;

try {
    $settingsDAL = new SiteSettingsDAL();
    $threshold = $settingsDAL->get('low_stock_threshold', '5');
    $lowStockThreshold = (int)$threshold;
} catch (Exception $e) {}

foreach ($cartItems as $item) {
    if (!empty($item['variation_id'])) {
        $variationDAL->decrementVariationStock((int)$item['variation_id'], (int)$item['quantity']);
        $variation = $variationDAL->getVariationById((int)$item['variation_id']);
        $product = $productDAL->getById($item['product_id']);
    } else {
        $productDAL->decrementStock($item['product_id'], $item['quantity']);
        $product = $productDAL->getById($item['product_id']);
    }
    if ($product && $product['stock'] < $lowStockThreshold) {
        $lowStockProducts[] = $product;
    }
}

// Queue emails/SMS/invoice in background so checkout responds instantly
$orderIdCopy = (int)$orderId;
$orderNumberCopy = $orderNumber;
$fullNameCopy = $fullName;
$phoneCopy = $phone;
$emailCopy = $email;
$totalCopy = $total;
$cartItemsCopy = $cartItems;
$lowStockCopy = $lowStockProducts;

AsyncMailQueue::add(function () use ($lowStockCopy) {
    if (!empty($lowStockCopy)) {
        lumak_send_low_stock_alert($lowStockCopy);
        ezymart_sms_notify_admin_low_stock($lowStockCopy);
    }
});
AsyncMailQueue::add(function () use ($orderNumberCopy, $fullNameCopy, $phoneCopy, $emailCopy, $totalCopy, $cartItemsCopy) {
    lumak_send_new_order_notification($orderNumberCopy, $fullNameCopy, $phoneCopy, $emailCopy, $totalCopy, $cartItemsCopy);
    ezymart_sms_notify_admin_new_order($orderNumberCopy, $fullNameCopy, $totalCopy);
});
AsyncMailQueue::add(function () use ($phoneCopy, $orderNumberCopy, $totalCopy) {
    ezymart_sms_notify_order_placed($phoneCopy, $orderNumberCopy, $totalCopy);
});
AsyncMailQueue::add(function () use ($orderIdCopy) {
    lumak_invoice_try_auto_send($orderIdCopy, 'order_placed');
});

// COD: Award points, clear cart, redirect to success
$pointsDAL = new PointsDAL();
$pointsDAL->addPoints($userId, $pointsToEarn, "Order #$orderNumber", $orderId);

$cartDAL->clearCart($userId);
unset($_SESSION['applied_coupon']);
$_SESSION['order_success_token'] = bin2hex(random_bytes(16));

header('Location: ' . BASE_URL . 'order-success.php');
AsyncMailQueue::flushAfterRedirect();
exit;

} catch (Throwable $e) {
    error_log('OrderController error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    $_SESSION['checkout_error'] = 'Unable to place your order. Please try again or contact support.';
    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}
