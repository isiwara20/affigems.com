<?php
$pageTitle = "Checkout - ARIGATO.LK";
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/CartDAL.php';
require_once __DIR__ . '/../dal/CouponDAL.php';
require_once __DIR__ . '/../dal/AddressDAL.php';
require_once __DIR__ . '/../helpers/payment.php';

// Must be logged in (before any HTML output)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])) {
    $_SESSION['redirect_after_login'] = BASE_URL . 'checkout.php';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$cartDAL = new CartDAL();
$cartItems = $cartDAL->getByUser($_SESSION['user_id']);

// Redirect to cart if empty (before any HTML output)
if (empty($cartItems)) {
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

require_once __DIR__ . '/partials/header.php';

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Calculate discount
$discount = 0;
$appliedCode = $_SESSION['applied_coupon'] ?? '';
if (!empty($appliedCode)) {
    try {
        $couponDAL = new CouponDAL();
        $result = $couponDAL->validateCoupon($appliedCode, $subtotal);
        if ($result['valid']) {
            $discount = $result['discount'];
        }
    } catch (Exception $e) {}
}

$deliveryMethod = 'standard';
$cartCount = count($cartItems);

// Fetch saved addresses
$savedAddresses = [];
try {
    $addressDAL = new AddressDAL();
    $savedAddresses = $addressDAL->getByUser($_SESSION['user_id']);
} catch (Exception $e) {}
$hasAddresses = !empty($savedAddresses);

// Fetch payment method settings
require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
$settingsDAL = new SiteSettingsDAL();
$codEnabled = $settingsDAL->get('payment_cod_enabled', '1') === '1';
$cardEnabled = $settingsDAL->get('payment_card_enabled', '1') === '1';
$onlinePaymentEnabled = $cardEnabled && !empty(ezymart_payment_available_gateways());

// Fetch ordering process settings
$placeOrderEnabled = $settingsDAL->get('checkout_place_order_enabled', '1') === '1';
$whatsappOrderEnabled = $settingsDAL->get('checkout_whatsapp_order_enabled', '1') === '1';
// Fallback: if both disabled, enable place order as default
if (!$placeOrderEnabled && !$whatsappOrderEnabled) {
    $placeOrderEnabled = true;
}

// Delivery fee from settings
require_once __DIR__ . '/../dal/ShippingRatesDAL.php';
require_once __DIR__ . '/../helpers/sri_lanka_locations.php';
$shippingDAL = new ShippingRatesDAL();
$shippingCountries = $shippingDAL->getEnabledCountries();
$shippingFeeMap = $shippingDAL->getFeeMapForCheckout();
$defaultCountry = ShippingRatesDAL::SRI_LANKA_CODE;
$deliveryFee = $shippingDAL->calculateFee($defaultCountry, $subtotal);
$total = $subtotal - $discount + $deliveryFee;

$checkoutError = $_SESSION['checkout_error'] ?? '';
unset($_SESSION['checkout_error']);

$slProvinces = ezymart_sl_provinces();
$slLocationsJson = json_encode(ezymart_sl_locations_for_js(), JSON_UNESCAPED_UNICODE);
?>

<style>
.checkout-section { padding: 30px 0 60px; background: linear-gradient(180deg, var(--purple-soft, #FFF8E6) 0%, #fff 140px); }
.checkout-title { font-size: 1.6rem; font-weight: 700; color: var(--purple-dark, #D4A017); margin-bottom: 4px; display: flex; align-items: center; gap: 10px; }
.checkout-title i { font-size: 1.2rem; color: var(--purple-primary, #E5A91A); }
.checkout-subtitle { font-size: 13px; color: #888; margin-bottom: 28px; }

.checkout-layout { display: grid; grid-template-columns: 1fr 380px; gap: 30px; align-items: start; }

/* Form Sections */
.checkout-form-section { background: #fff; border: 1px solid #F5E6B8; border-radius: 14px; padding: 28px; margin-bottom: 20px; box-shadow: 0 4px 20px rgba(196, 138, 10, 0.04); }
.section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 22px; }
.section-number { width: 28px; height: 28px; background: #D49A0A; color: #1a1a1a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
.section-header h3 { font-size: 15px; font-weight: 600; color: var(--purple-dark, #D4A017); }

.checkout-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
.checkout-form-group { margin-bottom: 16px; }
.checkout-form-group label { display: block; font-size: 12px; font-weight: 600; color: #333; margin-bottom: 6px; }
.checkout-form-group label .required { color: #ef4444; }
.checkout-input-wrap { display: flex; align-items: center; border: 1.5px solid #F5E6B8; border-radius: 10px; padding: 0 14px; background: #FFF8E6; transition: all 0.3s; }
.checkout-input-wrap:focus-within { border-color: var(--purple-primary, #E5A91A); background: #fff; box-shadow: 0 0 0 3px rgba(229, 169, 26, 0.1); }
.checkout-input-wrap i { color: var(--purple-primary, #E5A91A); font-size: 13px; margin-right: 10px; }
.checkout-input-wrap input, .checkout-input-wrap select, .checkout-input-wrap textarea { flex: 1; padding: 12px 0; border: none; outline: none; font-family: 'Poppins', sans-serif; font-size: 13px; color: #333; background: transparent; }
.checkout-input-wrap select { cursor: pointer; }
.checkout-input-wrap textarea { resize: vertical; min-height: 60px; }

/* Delivery Method */
.delivery-option { display: flex; align-items: center; gap: 14px; padding: 16px 18px; border: 1.5px solid #F5E6B8; border-radius: 10px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s; }
.delivery-option:hover { border-color: #F0D78C; }
.delivery-option.selected { border-color: var(--purple-primary, #E5A91A); background: var(--purple-soft, #FFF8E6); }
.delivery-option-icon { width: 40px; height: 40px; background: var(--purple-lavender, #FFE8A8); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--purple-primary, #E5A91A); font-size: 16px; }
.delivery-option-info { flex: 1; }
.delivery-option-info strong { display: block; font-size: 13px; color: var(--purple-dark, #D4A017); }
.delivery-option-info span { font-size: 11px; color: #888; }
.delivery-option-price { font-size: 14px; font-weight: 600; color: var(--purple-dark, #D4A017); }
.delivery-option-radio { width: 18px; height: 18px; border: 2px solid #F0D78C; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.delivery-option.selected .delivery-option-radio { border-color: var(--purple-primary, #E5A91A); }
.delivery-option.selected .delivery-option-radio::after { content: ''; width: 10px; height: 10px; background: var(--purple-primary, #E5A91A); border-radius: 50%; }

/* Payment Method */
.payment-option { display: flex; align-items: center; gap: 14px; padding: 16px 18px; border: 1.5px solid #F5E6B8; border-radius: 10px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s; }
.payment-option:hover { border-color: #F0D78C; }
.payment-option.selected { border-color: var(--purple-primary, #E5A91A); background: var(--purple-soft, #FFF8E6); }
.payment-option-icon { width: 40px; height: 40px; background: var(--purple-lavender, #FFE8A8); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--purple-primary, #E5A91A); font-size: 16px; }
.payment-option-info { flex: 1; }
.payment-option-info strong { display: block; font-size: 13px; color: var(--purple-dark, #D4A017); }
.payment-option-info span { font-size: 11px; color: #888; }
.payment-option-radio { width: 18px; height: 18px; border: 2px solid #F0D78C; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.payment-option.selected .payment-option-radio { border-color: var(--purple-primary, #E5A91A); }
.payment-option.selected .payment-option-radio::after { content: ''; width: 10px; height: 10px; background: var(--purple-primary, #E5A91A); border-radius: 50%; }
.payment-logos { display: flex; gap: 6px; margin-top: 2px; }
.payment-logos img { height: 18px; }

/* Order Summary Sidebar */
.checkout-summary { background: #fff; border: 1px solid #F5E6B8; border-radius: 14px; padding: 28px; position: sticky; top: 130px; box-shadow: 0 4px 20px rgba(196, 138, 10, 0.06); }
.checkout-summary h3 { font-size: 16px; font-weight: 700; color: var(--purple-dark, #D4A017); margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #FFE8A8; display: flex; align-items: center; gap: 8px; }
.checkout-summary h3 i { color: var(--purple-primary, #E5A91A); }

.checkout-item { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid #FFE8A8; }
.checkout-item:last-of-type { border-bottom: none; }
.checkout-item-img { width: 55px; height: 55px; border-radius: 8px; object-fit: cover; border: 1px solid #F5E6B8; flex-shrink: 0; }
.checkout-item-info { flex: 1; }
.checkout-item-info h4 { font-size: 12px; font-weight: 600; color: #333; line-height: 1.4; margin-bottom: 3px; }
.checkout-item-info span { font-size: 11px; color: #888; }
.checkout-item-price { font-size: 13px; font-weight: 700; color: var(--purple-dark, #D4A017); white-space: nowrap; }

.checkout-totals { margin-top: 18px; padding-top: 16px; border-top: 1px solid #FFE8A8; }
.checkout-total-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 13px; }
.checkout-total-row span { color: #666; }
.checkout-total-row strong { color: var(--purple-dark, #D4A017); }
.checkout-total-row.discount strong { color: #16a34a; }
.checkout-total-row.grand-total { border-top: 1.5px solid var(--purple-dark, #D4A017); margin-top: 10px; padding-top: 12px; }
.checkout-total-row.grand-total span { font-size: 15px; font-weight: 600; color: var(--purple-dark, #D4A017); }
.checkout-total-row.grand-total strong { font-size: 20px; font-weight: 800; color: var(--purple-dark, #D4A017); }

.btn-place-order { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 16px; background: #D49A0A; color: #1a1a1a; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 18px; font-family: 'Poppins', sans-serif; transition: all 0.3s; }
.btn-place-order:hover { background: #D4A017; box-shadow: 0 6px 20px rgba(229, 169, 26, 0.25); transform: translateY(-1px); }
.btn-place-order i { background: rgba(255,255,255,0.15); padding: 5px 8px; border-radius: 6px; }

.checkout-or { text-align: center; font-size: 12px; color: #999; margin: 12px 0; }
.btn-whatsapp-order { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 13px; background: #fff; color: #25d366; border: 1.5px solid #25d366; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; transition: all 0.3s; text-decoration: none; }
.btn-whatsapp-order:hover { background: #25d366; color: #fff; }

.checkout-trust { margin-top: 20px; padding-top: 18px; border-top: 1px solid #FFE8A8; }
.checkout-trust-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 0;
}
.checkout-trust-item:not(:last-child) {
    border-bottom: 1px solid #f8f4ff;
}
.checkout-trust-item i {
    font-size: 16px;
    color: var(--purple-primary, #E5A91A);
    width: 38px;
    height: 38px;
    min-width: 38px;
    border-radius: 10px;
    background: var(--purple-lavender, #FFE8A8);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.checkout-trust-item > div {
    flex: 1;
    min-width: 0;
    line-height: 1.4;
}
.checkout-trust-item div strong {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--purple-dark, #D4A017);
    margin-bottom: 2px;
}
.checkout-trust-item div span {
    display: block;
    font-size: 11px;
    color: #888;
}
.checkout-country-note { font-size: 11px; color: #888; margin-top: 6px; }
.checkout-sl-fields { display: block; }
.checkout-intl-fields { display: none; }

/* Responsive */
@media (max-width: 992px) {
    .checkout-layout { grid-template-columns: 1fr; }
    .checkout-summary { position: static; }
}
@media (max-width: 768px) {
    .checkout-form-row { grid-template-columns: 1fr; }
    .checkout-title { font-size: 1.3rem; }
    .checkout-section { padding: 20px 0 40px; }
    .checkout-section .container { padding: 0 12px; }
    .checkout-form-card { padding: 18px; border-radius: 12px; }
    .checkout-summary { border-radius: 12px; }
    .checkout-summary-body { padding: 18px; }
    .checkout-address-card { padding: 12px; }
    .btn-place-order { font-size: 14px; padding: 14px; }
}

/* Checkout Address Selection */
.checkout-address-section { margin-bottom: 20px; }
.checkout-address-options { display: flex; flex-direction: column; gap: 10px; }
.checkout-address-card {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 16px; border: 1.5px solid #F5E6B8; border-radius: 12px;
    cursor: pointer; transition: all 0.2s; background: #fff;
}
.checkout-address-card:hover { border-color: #F0D78C; }
.checkout-address-card.selected { border-color: var(--purple-primary, #E5A91A); background: var(--purple-soft, #FFF8E6); }
.checkout-addr-radio {
    width: 18px; height: 18px; border: 2px solid #F0D78C; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;
}
.checkout-address-card.selected .checkout-addr-radio { border-color: var(--purple-primary, #E5A91A); }
.checkout-address-card.selected .checkout-addr-radio::after { content: ''; width: 10px; height: 10px; background: var(--purple-primary, #E5A91A); border-radius: 50%; }
.checkout-addr-content { flex: 1; }
.checkout-addr-label { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
.checkout-addr-label i { font-size: 13px; color: var(--purple-primary, #E5A91A); }
.checkout-addr-label strong { font-size: 13px; color: var(--purple-dark, #D4A017); }
.checkout-addr-default { font-size: 9px; padding: 2px 7px; background: #D49A0A; color: #1a1a1a; border-radius: 10px; font-weight: 600; }
.checkout-addr-text { font-size: 12px; color: #555; line-height: 1.5; margin: 0; }
.checkout-addr-phone { font-size: 11px; color: #888; margin-top: 4px; display: flex; align-items: center; gap: 5px; }
.checkout-addr-phone i { font-size: 10px; }
.checkout-new-addr-trigger .checkout-addr-label i { color: var(--purple-primary, #E5A91A); }
.checkout-new-address-form { margin-top: 16px; padding-top: 16px; border-top: 1px solid #FFE8A8; }
</style>

<!-- Breadcrumb -->
<div class="product-page-breadcrumb">
    <div class="container">
        <a href="<?php echo BASE_URL; ?>">Home</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <a href="<?php echo BASE_URL; ?>cart.php">Cart</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <span>Checkout</span>
    </div>
</div>

<section class="checkout-section">
    <div class="container">
        <h1 class="checkout-title">Checkout <i class="fas fa-lock"></i></h1>
        <p class="checkout-subtitle">Please fill in the details below to complete your order</p>

        <?php if ($checkoutError): ?>
        <div class="alert alert-error" style="margin-bottom:20px;">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($checkoutError); ?>
        </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>controllers/OrderController.php" method="POST" data-turbo="false">
            <div class="checkout-layout">
                <!-- Left: Forms -->
                <div>
                    <!-- Billing & Delivery Info -->
                    <div class="checkout-form-section">
                        <div class="section-header">
                            <span class="section-number">1</span>
                            <h3>Billing & Delivery Information</h3>
                        </div>

                        <div class="checkout-form-row">
                            <div class="checkout-form-group">
                                <label>Full Name <span class="required">*</span></label>
                                <div class="checkout-input-wrap">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="full_name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="checkout-form-group">
                                <label>Mobile Number <span class="required">*</span></label>
                                <div class="checkout-input-wrap">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" name="phone" id="checkout_phone" placeholder="07X XXX XXXX" required>
                                </div>
                            </div>
                        </div>

                        <div class="checkout-form-row">
                            <div class="checkout-form-group">
                                <label>Email Address</label>
                                <div class="checkout-input-wrap">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" placeholder="your@email.com" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" readonly style="color:#888; cursor:not-allowed;">
                                </div>
                            </div>
                        </div>

                        <div class="checkout-form-group">
                            <label>Delivery Country <span class="required">*</span></label>
                            <div class="checkout-input-wrap">
                                <i class="fas fa-globe"></i>
                                <select name="shipping_country" id="shipping_country" required>
                                    <option value="LK">🇱🇰 Sri Lanka</option>
                                    <?php foreach ($shippingCountries as $country): ?>
                                    <option value="<?php echo htmlspecialchars($country['code']); ?>">
                                        <?php echo htmlspecialchars(trim(($country['flag'] ?? '') . ' ' . $country['name'])); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <p class="checkout-country-note" id="shippingCountryNote">Sri Lanka orders may qualify for free delivery above the store threshold.</p>
                        </div>

                        <!-- Address Selection -->
                        <?php if ($hasAddresses): ?>
                        <div class="checkout-address-section">
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--purple-dark, #D4A017); margin-bottom:12px;">Delivery Address</label>
                            
                            <div class="checkout-address-options" id="addressOptions">
                                <?php foreach ($savedAddresses as $idx => $addr): ?>
                                <label class="checkout-address-card <?php echo $addr['is_default'] ? 'selected' : ''; ?>" onclick="selectAddress(this)">
                                    <input type="radio" name="saved_address_id" value="<?php echo $addr['id']; ?>" <?php echo $addr['is_default'] ? 'checked' : ''; ?> style="display:none;">
                                    <div class="checkout-addr-radio"></div>
                                    <div class="checkout-addr-content">
                                        <div class="checkout-addr-label">
                                            <i class="fas fa-<?php echo strtolower($addr['label']) === 'office' ? 'building' : 'home'; ?>"></i>
                                            <strong><?php echo htmlspecialchars($addr['label']); ?></strong>
                                            <?php if ($addr['is_default']): ?><span class="checkout-addr-default">Default</span><?php endif; ?>
                                        </div>
                                        <p class="checkout-addr-text"><?php echo htmlspecialchars($addr['address']); ?></p>
                                        <p class="checkout-addr-text"><?php echo htmlspecialchars(implode(', ', array_filter([$addr['city'], $addr['district'], $addr['postal_code']]))); ?></p>
                                        <?php if (!empty($addr['phone'])): ?>
                                        <p class="checkout-addr-phone"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($addr['phone']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </label>
                                <?php endforeach; ?>

                                <!-- New Address Option -->
                                <label class="checkout-address-card checkout-new-addr-trigger" onclick="selectAddress(this)">
                                    <input type="radio" name="saved_address_id" value="new" style="display:none;">
                                    <div class="checkout-addr-radio"></div>
                                    <div class="checkout-addr-content">
                                        <div class="checkout-addr-label">
                                            <i class="fas fa-plus-circle"></i>
                                            <strong>Use a new address</strong>
                                        </div>
                                        <p class="checkout-addr-text" style="color:#6b7099;">Enter a different delivery address</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- New Address Form (hidden by default when addresses exist) -->
                        <div class="checkout-new-address-form" id="newAddressForm" style="display:none;">
                        <?php else: ?>
                        <!-- No saved addresses - show form directly -->
                        <input type="hidden" name="saved_address_id" value="new">
                        <div class="checkout-new-address-form" id="newAddressForm">
                        <?php endif; ?>

                            <div class="checkout-form-row checkout-sl-fields" id="checkoutSlFields">
                                <div class="checkout-form-group">
                                    <label>Province <span class="required">*</span></label>
                                    <div class="checkout-input-wrap">
                                        <i class="fas fa-map"></i>
                                        <select id="checkout_province_sl" <?php echo $hasAddresses ? '' : 'required'; ?>>
                                            <option value="">Select your province</option>
                                            <?php foreach ($slProvinces as $province): ?>
                                            <option value="<?php echo htmlspecialchars($province); ?>"><?php echo htmlspecialchars($province); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="checkout-form-group">
                                    <label>Town / City <span class="required">*</span></label>
                                    <div class="checkout-input-wrap">
                                        <i class="fas fa-city"></i>
                                        <select id="checkout_city_sl" <?php echo $hasAddresses ? 'disabled' : 'disabled'; ?>>
                                            <option value="">Select province first</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="checkout-form-row checkout-intl-fields" id="checkoutIntlFields">
                                <div class="checkout-form-group">
                                    <label>State / Province <span class="required">*</span></label>
                                    <div class="checkout-input-wrap">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <input type="text" id="checkout_state_intl" placeholder="State or province">
                                    </div>
                                </div>
                                <div class="checkout-form-group">
                                    <label>City <span class="required">*</span></label>
                                    <div class="checkout-input-wrap">
                                        <i class="fas fa-city"></i>
                                        <input type="text" id="checkout_city_intl" placeholder="Enter your city">
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="district" id="checkout_district" value="">
                            <input type="hidden" name="city" id="checkout_city_hidden" value="">

                            <div class="checkout-form-group">
                                <label>Postal Code</label>
                                <div class="checkout-input-wrap">
                                    <i class="fas fa-mail-bulk"></i>
                                    <input type="text" name="postal_code" id="checkout_postal" placeholder="Enter postal code">
                                </div>
                            </div>

                            <div class="checkout-form-group">
                                <label>Address Line 1 <span class="required">*</span></label>
                                <div class="checkout-input-wrap">
                                    <i class="fas fa-home"></i>
                                    <input type="text" name="address_1" id="checkout_address1" placeholder="House No, Street, Area" <?php echo $hasAddresses ? '' : 'required'; ?>>
                                </div>
                            </div>

                            <div class="checkout-form-group">
                                <label>Address Line 2 (Optional)</label>
                                <div class="checkout-input-wrap">
                                    <i class="fas fa-building"></i>
                                    <input type="text" name="address_2" placeholder="Apartment, Suite, Floor, etc.">
                                </div>
                            </div>

                            <?php if ($hasAddresses): ?>
                            <label style="display:flex; align-items:center; gap:8px; font-size:12px; color:#555; cursor:pointer; margin-top:8px;">
                                <input type="checkbox" name="save_new_address" value="1" checked style="accent-color:#E5A91A;"> Save this address to my address book
                            </label>
                            <?php endif; ?>
                        </div>

                        <div class="checkout-form-group" style="margin-top:16px;">
                            <label>Order Notes (Optional)</label>
                            <div class="checkout-input-wrap">
                                <i class="fas fa-sticky-note"></i>
                                <textarea name="notes" placeholder="Any special instructions for delivery"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Method -->
                    <div class="checkout-form-section">
                        <div class="section-header">
                            <span class="section-number">2</span>
                            <h3>Delivery Method</h3>
                        </div>

                        <label class="delivery-option selected" id="deliveryOptionStandard">
                            <div class="delivery-option-icon"><i class="fas fa-truck"></i></div>
                            <div class="delivery-option-info">
                                <strong id="deliveryMethodTitle">Standard Delivery</strong>
                                <span id="deliveryMethodDesc">Delivered within 1 - 3 working days (Sri Lanka)</span>
                            </div>
                            <span class="delivery-option-price" id="deliveryOptionPrice"><?php echo $deliveryFee === 0 ? 'FREE' : format_price($deliveryFee); ?></span>
                            <div class="delivery-option-radio"></div>
                            <input type="radio" name="delivery_method" value="standard" checked style="display:none;">
                        </label>
                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-form-section">
                        <div class="section-header">
                            <span class="section-number">3</span>
                            <h3>Payment Method</h3>
                        </div>

                        <?php $firstEnabled = true; ?>

                        <?php if ($codEnabled): ?>
                        <label class="payment-option <?php echo $firstEnabled ? 'selected' : ''; ?>" onclick="selectPayment(this)">
                            <div class="payment-option-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="payment-option-info">
                                <strong>Cash on Delivery</strong>
                                <span>Pay in cash when you receive your order</span>
                            </div>
                            <div class="payment-option-radio"></div>
                            <input type="radio" name="payment_method" value="cod" <?php echo $firstEnabled ? 'checked' : ''; ?> style="display:none;">
                        </label>
                        <?php $firstEnabled = false; ?>
                        <?php else: ?>
                        <div class="payment-option" style="opacity:0.5; cursor:not-allowed; pointer-events:none;">
                            <div class="payment-option-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="payment-option-info">
                                <strong>Cash on Delivery</strong>
                                <span style="color:#ef4444;">Currently unavailable</span>
                            </div>
                            <div class="payment-option-radio"></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($onlinePaymentEnabled): ?>
                        <label class="payment-option <?php echo $firstEnabled ? 'selected' : ''; ?>" onclick="selectPayment(this)">
                            <div class="payment-option-icon"><i class="fas fa-credit-card"></i></div>
                            <div class="payment-option-info">
                                <strong>Card Payment</strong>
                                <span>Secure online payments via Visa, Mastercard, PayHere, Payzy or PayKoko</span>
                                <div class="payment-logos">
                                    <img src="https://cdn.jsdelivr.net/gh/aaronfagan/svg-credit-card-payment-icons@main/flat-rounded/visa.svg" alt="Visa">
                                    <img src="https://cdn.jsdelivr.net/gh/aaronfagan/svg-credit-card-payment-icons@main/flat-rounded/mastercard.svg" alt="Mastercard">
                                </div>
                            </div>
                            <div class="payment-option-radio"></div>
                            <input type="radio" name="payment_method" value="card" <?php echo $firstEnabled ? 'checked' : ''; ?> style="display:none;">
                        </label>
                        <?php $firstEnabled = false; ?>
                        <?php else: ?>
                        <div class="payment-option" style="opacity:0.5; cursor:not-allowed; pointer-events:none;">
                            <div class="payment-option-icon"><i class="fas fa-credit-card"></i></div>
                            <div class="payment-option-info">
                                <strong>Card Payment</strong>
                                <span style="color:#ef4444;">Currently unavailable</span>
                            </div>
                            <div class="payment-option-radio"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Order Summary -->
                <div class="checkout-summary">
                    <h3><i class="fas fa-shopping-bag"></i> Order Summary</h3>

                    <?php foreach ($cartItems as $item): ?>
                    <div class="checkout-item">
                        <?php if ($item['image']): ?>
                        <img src="<?php echo htmlspecialchars(media_image_url($item['image'])); ?>" alt="" class="checkout-item-img">
                        <?php else: ?>
                        <div class="checkout-item-img" style="display:flex; align-items:center; justify-content:center; background:#f8f8f8; color:#ccc;"><i class="fas fa-tshirt"></i></div>
                        <?php endif; ?>
                        <div class="checkout-item-info">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <span>Qty: <?php echo $item['quantity']; ?></span>
                        </div>
                        <span class="checkout-item-price"><?php echo format_price($item['price'] * $item['quantity']); ?></span>
                    </div>
                    <?php endforeach; ?>

                    <div class="checkout-totals">
                        <div class="checkout-total-row">
                            <span>Subtotal (<?php echo $cartCount; ?> Items)</span>
                            <strong><?php echo format_price($subtotal); ?></strong>
                        </div>
                        <?php if ($discount > 0): ?>
                        <div class="checkout-total-row discount">
                            <span>Discount</span>
                            <strong>- <?php echo format_price($discount); ?></strong>
                        </div>
                        <?php endif; ?>
                        <div class="checkout-total-row">
                            <span>Delivery Fee</span>
                            <strong id="deliveryFeeDisplay"><?php echo $deliveryFee === 0 ? 'FREE' : format_price($deliveryFee); ?></strong>
                        </div>
                        <div class="checkout-total-row grand-total">
                            <span>Total</span>
                            <strong id="grandTotalDisplay"><?php echo format_price($total); ?></strong>
                        </div>
                    </div>

                    <input type="hidden" name="subtotal" value="<?php echo $subtotal; ?>">
                    <input type="hidden" name="discount" value="<?php echo $discount; ?>">
                    <input type="hidden" name="delivery_fee" id="deliveryFeeInput" value="<?php echo $deliveryFee; ?>">
                    <input type="hidden" name="total" id="totalInput" value="<?php echo $total; ?>">

                    <?php if ($placeOrderEnabled): ?>
                    <button type="submit" class="btn-place-order">
                        Place Order <i class="fas fa-arrow-right"></i>
                    </button>
                    <?php endif; ?>

                    <?php if ($placeOrderEnabled && $whatsappOrderEnabled): ?>
                    <p class="checkout-or">OR</p>
                    <?php endif; ?>

                    <?php if ($whatsappOrderEnabled): ?>
                    <a href="#" class="btn-whatsapp-order" onclick="placeOrderWhatsApp(event)" style="margin-top: 18px;">
                        <i class="fab fa-whatsapp"></i> Place Order with WhatsApp
                    </a>
                    <?php endif; ?>

                    <div class="checkout-trust">
                        <div class="checkout-trust-item">
                            <i class="fas fa-shield-alt"></i>
                            <div><strong>Secure Payments</strong><span>100% safe & secure</span></div>
                        </div>
                        <div class="checkout-trust-item">
                            <i class="fas fa-headset"></i>
                            <div><strong>Customer Support</strong><span>We're here to help</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
var checkoutSubtotal = <?php echo (float)$subtotal; ?>;
var checkoutDiscount = <?php echo (float)$discount; ?>;
var shippingFeeMap = <?php echo json_encode($shippingFeeMap, JSON_UNESCAPED_UNICODE); ?>;
var slLocations = <?php echo $slLocationsJson; ?>;

function populateSlTowns(province, selectedTown) {
    var townSelect = document.getElementById('checkout_city_sl');
    if (!townSelect) return;

    townSelect.innerHTML = '<option value="">' + (province ? 'Select town / city' : 'Select province first') + '</option>';
    townSelect.disabled = !province;
    townSelect.value = '';

    if (!province || !slLocations[province]) {
        syncDistrictField();
        return;
    }

    slLocations[province].forEach(function(item) {
        var opt = document.createElement('option');
        opt.value = item.town;
        opt.textContent = item.town;
        opt.dataset.district = item.district;
        townSelect.appendChild(opt);
    });

    if (selectedTown) {
        for (var i = 0; i < townSelect.options.length; i++) {
            if (townSelect.options[i].value === selectedTown) {
                townSelect.selectedIndex = i;
                break;
            }
        }
    }

    townSelect.disabled = false;
    townSelect.required = document.getElementById('newAddressForm').style.display !== 'none'
        && document.getElementById('shipping_country').value === 'LK';
    syncDistrictField();
}

function onProvinceChange() {
    var province = document.getElementById('checkout_province_sl').value;
    populateSlTowns(province, '');
}

function onSlTownChange() {
    syncDistrictField();
}

function calculateShippingFee(countryCode) {
    countryCode = (countryCode || 'LK').toUpperCase();
    if (countryCode === 'LK') {
        var lk = shippingFeeMap.LK || { fee: 0, free_threshold: 0 };
        if (lk.free_threshold > 0 && checkoutSubtotal >= lk.free_threshold) {
            return 0;
        }
        return parseInt(lk.fee, 10) || 0;
    }
    if (shippingFeeMap[countryCode]) {
        return parseInt(shippingFeeMap[countryCode].fee, 10) || 0;
    }
    return parseInt((shippingFeeMap._rest || {}).fee, 10) || 0;
}

function updateCheckoutTotals(fee) {
    document.getElementById('deliveryFeeDisplay').textContent = fee === 0 ? 'FREE' : (typeof formatStorePrice === 'function' ? formatStorePrice(fee) : ('LKR ' + fee.toLocaleString()));
    document.getElementById('deliveryFeeInput').value = fee;
    document.getElementById('deliveryOptionPrice').textContent = fee === 0 ? 'FREE' : (typeof formatStorePrice === 'function' ? formatStorePrice(fee) : ('LKR ' + fee.toLocaleString()));
    var total = checkoutSubtotal - checkoutDiscount + fee;
    document.getElementById('grandTotalDisplay').textContent = typeof formatStorePrice === 'function' ? formatStorePrice(total) : ('LKR ' + total.toLocaleString());
    document.getElementById('totalInput').value = total;
}

function syncDistrictField() {
    var country = document.getElementById('shipping_country').value;
    var hiddenDistrict = document.getElementById('checkout_district');
    var hiddenCity = document.getElementById('checkout_city_hidden');
    if (country === 'LK') {
        var townSelect = document.getElementById('checkout_city_sl');
        var selected = townSelect ? townSelect.options[townSelect.selectedIndex] : null;
        hiddenDistrict.value = (selected && selected.dataset.district) ? selected.dataset.district : '';
        hiddenCity.value = townSelect ? townSelect.value : '';
    } else {
        hiddenDistrict.value = document.getElementById('checkout_state_intl').value;
        hiddenCity.value = document.getElementById('checkout_city_intl').value;
    }
}

function setSlLocationFields(province, town, district) {
    var provinceSelect = document.getElementById('checkout_province_sl');
    if (!provinceSelect) return;

    if (province) {
        provinceSelect.value = province;
    } else if (district) {
        Object.keys(slLocations).some(function(key) {
            var match = slLocations[key].some(function(item) {
                return item.district === district;
            });
            if (match) {
                province = key;
                provinceSelect.value = key;
            }
            return match;
        });
    }

    populateSlTowns(provinceSelect.value, town || '');
}

function onShippingCountryChange() {
    var country = document.getElementById('shipping_country').value;
    var isSl = country === 'LK';
    document.getElementById('checkoutSlFields').style.display = isSl ? 'grid' : 'none';
    document.getElementById('checkoutIntlFields').style.display = isSl ? 'none' : 'grid';

    var slProvince = document.getElementById('checkout_province_sl');
    var intlState = document.getElementById('checkout_state_intl');
    var citySl = document.getElementById('checkout_city_sl');
    var cityIntl = document.getElementById('checkout_city_intl');
    var formVisible = document.getElementById('newAddressForm').style.display !== 'none';

    if (slProvince) slProvince.required = isSl && formVisible;
    if (intlState) intlState.required = !isSl && formVisible;
    if (citySl) citySl.required = isSl && formVisible;
    if (cityIntl) cityIntl.required = !isSl && formVisible;

    var note = document.getElementById('shippingCountryNote');
    var title = document.getElementById('deliveryMethodTitle');
    var desc = document.getElementById('deliveryMethodDesc');

    if (isSl) {
        note.textContent = 'Sri Lanka orders may qualify for free delivery above the store threshold.';
        title.textContent = 'Standard Delivery';
        desc.textContent = 'Delivered within 1 - 3 working days within Sri Lanka';
    } else {
        var label = (shippingFeeMap[country] && shippingFeeMap[country].label) ? shippingFeeMap[country].label.trim() : 'International';
        note.textContent = 'International shipping fee applies for ' + label + '.';
        title.textContent = 'International Shipping';
        desc.textContent = 'Worldwide delivery to your selected country';
    }

    updateCheckoutTotals(calculateShippingFee(country));
    syncDistrictField();
}

function selectDelivery(el, method, price) {
    document.querySelectorAll('.delivery-option').forEach(function(o) { o.classList.remove('selected'); });
    el.classList.add('selected');
    el.querySelector('input[type="radio"]').checked = true;
    updateCheckoutTotals(price);
}

function selectPayment(el) {
    document.querySelectorAll('.payment-option').forEach(function(o) { o.classList.remove('selected'); });
    el.classList.add('selected');
    el.querySelector('input[type="radio"]').checked = true;
}

// Address selection
function selectAddress(el) {
    document.querySelectorAll('.checkout-address-card').forEach(function(c) { c.classList.remove('selected'); });
    el.classList.add('selected');
    el.querySelector('input[type="radio"]').checked = true;
    
    var val = el.querySelector('input[type="radio"]').value;
    var form = document.getElementById('newAddressForm');
    
    if (val === 'new') {
        form.style.display = 'block';
        toggleNewAddressRequired(true);
        onShippingCountryChange();
    } else {
        form.style.display = 'none';
        toggleNewAddressRequired(false);
        document.getElementById('shipping_country').value = 'LK';
        onShippingCountryChange();
        <?php foreach ($savedAddresses as $addr): ?>
        if (val == '<?php echo $addr['id']; ?>') {
            document.getElementById('checkout_phone').value = '<?php echo htmlspecialchars($addr['phone'] ?? ''); ?>';
            document.getElementById('checkout_district').value = '<?php echo htmlspecialchars($addr['district'] ?? ''); ?>';
        }
        <?php endforeach; ?>
    }
}

function toggleNewAddressRequired(required) {
    var country = document.getElementById('shipping_country').value;
    var isSl = country === 'LK';
    var slProvince = document.getElementById('checkout_province_sl');
    var intlState = document.getElementById('checkout_state_intl');
    var citySl = document.getElementById('checkout_city_sl');
    var cityIntl = document.getElementById('checkout_city_intl');
    var addr = document.getElementById('checkout_address1');
    if (slProvince) slProvince.required = required && isSl;
    if (intlState) intlState.required = required && !isSl;
    if (citySl) {
        citySl.required = required && isSl;
        citySl.disabled = !required || !isSl || !slProvince || !slProvince.value;
    }
    if (cityIntl) cityIntl.required = required && !isSl;
    if (addr) addr.required = required;
}

// Set phone from default address on load
<?php if ($hasAddresses): ?>
(function() {
    <?php foreach ($savedAddresses as $addr): ?>
    <?php if ($addr['is_default'] && !empty($addr['phone'])): ?>
    document.getElementById('checkout_phone').value = '<?php echo htmlspecialchars($addr['phone']); ?>';
    <?php endif; ?>
    <?php endforeach; ?>
})();
<?php endif; ?>

document.getElementById('shipping_country').addEventListener('change', onShippingCountryChange);
document.getElementById('checkout_province_sl').addEventListener('change', onProvinceChange);
document.getElementById('checkout_city_sl').addEventListener('change', onSlTownChange);
document.getElementById('checkout_state_intl').addEventListener('input', syncDistrictField);
document.getElementById('checkout_city_intl').addEventListener('input', syncDistrictField);
document.querySelector('form[action*="OrderController"]').addEventListener('submit', function(e) {
    syncDistrictField();
    var country = document.getElementById('shipping_country').value;
    var formVisible = document.getElementById('newAddressForm').style.display !== 'none';
    if (country === 'LK' && formVisible) {
        var province = document.getElementById('checkout_province_sl').value;
        var city = document.getElementById('checkout_city_hidden').value;
        if (!province || !city) {
            e.preventDefault();
            alert('Please select your province and town / city.');
            return;
        }
    }
});

onShippingCountryChange();

var waOrderSuccessUrl = '<?php echo BASE_URL; ?>order-success.php';

function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
        || (navigator.maxTouchPoints > 1 && window.innerWidth < 1024);
}

function openWhatsAppPrepareTab() {
    var tab = window.open('about:blank', '_blank');
    if (!tab) return null;
    try {
        tab.document.open();
        tab.document.write(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><title>WhatsApp</title>' +
            '<style>body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f0fdf4;color:#166534;font-size:15px}</style></head>' +
            '<body><p>Preparing your order message...</p></body></html>'
        );
        tab.document.close();
    } catch (ignore) {}
    return tab;
}

function openWhatsAppDesktopTab(tab, appUrl, apiUrl, webUrl) {
    if (!tab || tab.closed) return;
    if (appUrl) {
        tab.location.href = appUrl;
        setTimeout(function() {
            try {
                if (!tab.closed) tab.location.href = apiUrl || webUrl;
            } catch (ignore) {
                tab.location.href = apiUrl || webUrl;
            }
        }, 1500);
        return;
    }
    tab.location.href = apiUrl || webUrl;
}

function storeWhatsAppPending(appUrl, apiUrl, webUrl) {
    sessionStorage.setItem('ezymart_wa_pending', '1');
    sessionStorage.setItem('ezymart_wa_app', appUrl || '');
    sessionStorage.setItem('ezymart_wa_api', apiUrl || '');
    sessionStorage.setItem('ezymart_wa_web', webUrl || '');
}

function placeOrderWhatsApp(e) {
    e.preventDefault();

    var btn = document.querySelector('.btn-whatsapp-order');
    if (btn.dataset.submitting === 'true') return;
    btn.dataset.submitting = 'true';
    btn.style.opacity = '0.7';
    btn.style.pointerEvents = 'none';
    var originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    var waTab = openWhatsAppPrepareTab();

    syncDistrictField();

    var form = document.querySelector('form[action*="OrderController"]');
    var formData = new FormData(form);
    formData.append('payment_method', 'whatsapp');

    fetch('<?php echo BASE_URL; ?>controllers/WhatsAppOrderController.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) {
        return r.text().then(function(text) {
            try {
                return JSON.parse(text);
            } catch (err) {
                console.error('WhatsApp order response:', text);
                throw new Error('Invalid server response');
            }
        });
    })
    .then(function(data) {
        if (data.success) {
            var webUrl = data.whatsapp_url || '';
            var appUrl = data.whatsapp_app_url || '';
            var apiUrl = data.whatsapp_api_url || '';
            if (!webUrl && data.whatsapp_message) {
                webUrl = 'https://wa.me/<?php echo STORE_WHATSAPP; ?>?text=' + encodeURIComponent(data.whatsapp_message);
                apiUrl = 'https://api.whatsapp.com/send?phone=<?php echo STORE_WHATSAPP; ?>&text=' + encodeURIComponent(data.whatsapp_message);
                appUrl = 'whatsapp://send?phone=<?php echo STORE_WHATSAPP; ?>&text=' + encodeURIComponent(data.whatsapp_message);
            }

            if (isMobileDevice()) {
                if (waTab && !waTab.closed) {
                    try { waTab.close(); } catch (ignore) {}
                }
                if (appUrl || apiUrl || webUrl) {
                    storeWhatsAppPending(appUrl, apiUrl, webUrl);
                    if (appUrl) {
                        var waLink = document.createElement('a');
                        waLink.href = appUrl;
                        waLink.style.display = 'none';
                        document.body.appendChild(waLink);
                        waLink.click();
                        document.body.removeChild(waLink);
                    }
                }
                window.location.replace(waOrderSuccessUrl);
                return;
            }

            if (appUrl || apiUrl || webUrl) {
                if (waTab && !waTab.closed) {
                    openWhatsAppDesktopTab(waTab, appUrl, apiUrl, webUrl);
                } else {
                    openWhatsAppDesktopTab(window.open('about:blank', '_blank'), appUrl, apiUrl, webUrl);
                }
            } else if (waTab && !waTab.closed) {
                try { waTab.close(); } catch (ignore) {}
            }

            window.location.replace(waOrderSuccessUrl);
            return;
        }

        if (waTab && !waTab.closed) {
            try { waTab.close(); } catch (ignore) {}
        }
        alert(data.error || 'Failed to place order. Please try again.');
        btn.dataset.submitting = 'false';
        btn.style.opacity = '';
        btn.style.pointerEvents = '';
        btn.innerHTML = originalHTML;
    })
    .catch(function(err) {
        console.error('WhatsApp order failed:', err);
        if (waTab && !waTab.closed) {
            try { waTab.close(); } catch (ignore) {}
        }
        alert('Something went wrong. Please try again.');
        btn.dataset.submitting = 'false';
        btn.style.opacity = '';
        btn.style.pointerEvents = '';
        btn.innerHTML = originalHTML;
    });
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
