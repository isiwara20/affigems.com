<?php
/**
 * WhatsApp Order Controller
 * Creates an order in the database when a user places an order via WhatsApp.
 * Returns JSON with order number so it can be included in the WhatsApp message.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/OrderDAL.php';
require_once __DIR__ . '/../dal/CartDAL.php';
require_once __DIR__ . '/../dal/CouponDAL.php';
require_once __DIR__ . '/../dal/PointsDAL.php';
require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
require_once __DIR__ . '/../services/AsyncMailQueue.php';
require_once __DIR__ . '/../helpers/whatsapp.php';

// Catch all errors
try {

// Must be logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Please login to place an order.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

$userId = $_SESSION['user_id'];

// Get cart items
$cartDAL = new CartDAL();
$cartItems = $cartDAL->getByUser($userId);

if (empty($cartItems)) {
    echo json_encode(['success' => false, 'error' => 'Your cart is empty.']);
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
        $couponDAL->incrementUsage($result['coupon']['id']);
    }
}

// Delivery fee from country
require_once __DIR__ . '/../dal/ShippingRatesDAL.php';
$settingsDAL = new SiteSettingsDAL();
$shippingDAL = new ShippingRatesDAL();
$shippingCountry = strtoupper(trim($_POST['shipping_country'] ?? ShippingRatesDAL::SRI_LANKA_CODE));

// Generate order number (WA prefix for WhatsApp orders)
$orderNumber = 'WA' . date('ymd') . strtoupper(substr(uniqid(), -4));

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
    // Save new address
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
        } catch (Exception $e) {}
    }
}

$deliveryFee = $shippingDAL->calculateFee($shippingCountry, $subtotal);
$total = $subtotal - $discount + $deliveryFee;
$pointsToEarn = PointsDAL::calculatePoints($total);

// Validate (match standard checkout requirements)
if (empty($fullName) || empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Please fill in your name and phone number.']);
    exit;
}
if ($shippingCountry === ShippingRatesDAL::SRI_LANKA_CODE && (empty($district) || empty($city) || empty($address1))) {
    echo json_encode(['success' => false, 'error' => 'Please fill in your delivery address.']);
    exit;
}

// Create order with payment_method = 'whatsapp'
$orderDAL = new OrderDAL();

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
    ':coupon_code' => !empty($couponCode) ? $couponCode : '',
    ':delivery_method' => $deliveryMethod,
    ':payment_method' => 'whatsapp',
    ':payment_status' => 'pending',
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

// Deduct stock and check for low stock
require_once __DIR__ . '/../dal/ProductDAL.php';
require_once __DIR__ . '/../dal/ProductVariationDAL.php';
require_once __DIR__ . '/../services/AsyncMailQueue.php';
$productDAL = new ProductDAL();
$variationDAL = new ProductVariationDAL();
$lowStockProducts = [];
$lowStockThreshold = 5;

try {
    $threshold = $settingsDAL->get('low_stock_threshold', '5');
    $lowStockThreshold = (int)$threshold;
} catch (Exception $e) {}

foreach ($cartItems as $item) {
    if (!empty($item['variation_id'])) {
        $variationDAL->decrementVariationStock((int)$item['variation_id'], (int)$item['quantity']);
        $product = $productDAL->getById($item['product_id']);
    } else {
        $productDAL->decrementStock($item['product_id'], $item['quantity']);
        $product = $productDAL->getById($item['product_id']);
    }
    if ($product && $product['stock'] < $lowStockThreshold) {
        $lowStockProducts[] = $product;
    }
}

// Award points
$pointsDAL = new PointsDAL();
$pointsDAL->addPoints($userId, $pointsToEarn, "Order #$orderNumber", $orderId);

// Clear cart and coupon
$cartDAL->clearCart($userId);
unset($_SESSION['applied_coupon']);

// Store order info in session for success page
$_SESSION['last_order_id'] = $orderId;
$_SESSION['last_order_number'] = $orderNumber;
$_SESSION['order_success_token'] = bin2hex(random_bytes(16));

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
    lumak_send_new_order_notification($orderNumberCopy, $fullNameCopy, $phoneCopy, $emailCopy, $totalCopy, $cartItemsCopy, ['whatsapp' => true]);
    ezymart_sms_notify_admin_new_order($orderNumberCopy, $fullNameCopy, $totalCopy, ['whatsapp' => true]);
});
AsyncMailQueue::add(function () use ($phoneCopy, $orderNumberCopy, $totalCopy) {
    ezymart_sms_notify_order_placed($phoneCopy, $orderNumberCopy, $totalCopy);
});
AsyncMailQueue::add(function () use ($orderIdCopy) {
    lumak_invoice_try_auto_send($orderIdCopy, 'order_placed');
});

$addressLine = trim(implode(', ', array_filter([
    $address1,
    $address2,
    $city,
    $district,
    $postalCode,
])));
$whatsappMessage = ezymart_whatsapp_order_message(
    $orderNumber,
    $fullName,
    $phone,
    $email,
    $addressLine,
    $cartItems,
    (float)$total
);
$whatsappUrl = ezymart_whatsapp_order_url($whatsappMessage);
$whatsappUrls = ezymart_whatsapp_open_urls($whatsappMessage);

AsyncMailQueue::sendResponse([
    'success' => true,
    'order_number' => $orderNumber,
    'order_id' => $orderId,
    'whatsapp_url' => $whatsappUrl,
    'whatsapp_app_url' => $whatsappUrls['app'],
    'whatsapp_api_url' => $whatsappUrls['api'],
    'whatsapp_message' => $whatsappMessage,
]);
AsyncMailQueue::flushAfterResponse();
exit;

} catch (Throwable $e) {
    error_log('WhatsApp order error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['success' => false, 'error' => 'Server error. Please try again.']);
}
