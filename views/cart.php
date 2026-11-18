<?php
$pageTitle = "Your Cart - ARIGATO.LK";
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/../dal/CartDAL.php';
require_once __DIR__ . '/../dal/ProductDAL.php';
require_once __DIR__ . '/../dal/CouponDAL.php';

$cartItems = [];
$cartCount = 0;
$subtotal = 0;
$discount = 0;
$couponMessage = '';
$couponError = '';
$appliedCode = $_SESSION['applied_coupon'] ?? '';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin'])) {
    try {
        $cartDAL = new CartDAL();
        $cartItems = $cartDAL->getByUser($_SESSION['user_id']);
        $cartCount = count($cartItems);
        foreach ($cartItems as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
    } catch (Exception $e) {
        $cartItems = [];
    }
}

// Handle coupon apply/remove
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['coupon_action'])) {
    if ($_POST['coupon_action'] === 'apply') {
        $code = trim($_POST['promo_code'] ?? '');
        if (!empty($code)) {
            try {
                $couponDAL = new CouponDAL();
                $result = $couponDAL->validateCoupon($code, $subtotal);
                if ($result['valid']) {
                    $_SESSION['applied_coupon'] = strtoupper($code);
                    $appliedCode = strtoupper($code);
                    $couponMessage = $result['message'];
                } else {
                    $couponError = $result['message'];
                }
            } catch (Exception $e) {
                $couponError = 'Error applying coupon.';
            }
        }
    } elseif ($_POST['coupon_action'] === 'remove') {
        unset($_SESSION['applied_coupon']);
        $appliedCode = '';
    }
}

// Calculate discount if coupon is applied
if (!empty($appliedCode) && $subtotal > 0) {
    try {
        $couponDAL = new CouponDAL();
        $result = $couponDAL->validateCoupon($appliedCode, $subtotal);
        if ($result['valid']) {
            $discount = $result['discount'];
        } else {
            unset($_SESSION['applied_coupon']);
            $appliedCode = '';
        }
    } catch (Exception $e) {}
}

// Delivery fee from admin settings
require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
$settingsDAL = new SiteSettingsDAL();
$deliveryFeeAmount = (int)$settingsDAL->get('delivery_fee', '250');
$freeDeliveryThreshold = (int)$settingsDAL->get('free_delivery_threshold', '2500');
$deliveryFee = ($freeDeliveryThreshold > 0 && $subtotal >= $freeDeliveryThreshold) ? 0 : $deliveryFeeAmount;
$total = $subtotal - $discount + $deliveryFee;

// Get random products for "You May Also Like"
try {
    $productDAL = new ProductDAL();
    $featuredProducts = $productDAL->getActive();
    shuffle($featuredProducts);
    $featuredProducts = array_slice($featuredProducts, 0, 5);
} catch (Exception $e) {
    $featuredProducts = [];
}
?>

<style>
.cart-section { padding: 30px 0 60px; overflow-x: hidden; max-width: 100vw; background: linear-gradient(180deg, var(--purple-soft, #FFF8E6) 0%, #fff 140px); }
.cart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
.cart-header h1 { font-size: 1.6rem; font-weight: 700; color: var(--purple-dark, #D4A017); }
.cart-header h1 span { font-weight: 400; color: #888; font-size: 1rem; }
.continue-shopping { font-size: 13px; color: var(--purple-dark, #D4A017); text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 6px; }
.continue-shopping:hover { color: var(--purple-primary, #E5A91A); }

.cart-layout { display: grid; grid-template-columns: 1fr 340px; gap: 30px; align-items: start; }

/* Cart Table */
.cart-table-wrap { background: #fff; border: 1px solid #F5E6B8; border-radius: 14px; overflow: hidden; max-height: 700px; overflow-y: auto; box-shadow: 0 4px 20px rgba(196, 138, 10, 0.06); }
.cart-table-wrap::-webkit-scrollbar { width: 5px; }
.cart-table-wrap::-webkit-scrollbar-thumb { background: #F0D78C; border-radius: 5px; }
.cart-table-wrap::-webkit-scrollbar-thumb:hover { background: var(--purple-primary, #E5A91A); }
.cart-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.cart-table thead th { background: #D49A0A; color: #1a1a1a; padding: 14px 18px; font-size: 12px; font-weight: 600; text-align: left; text-transform: uppercase; letter-spacing: 0.5px; position: sticky; top: 0; z-index: 2; }
.cart-table thead th:nth-child(1) { width: 40%; }
.cart-table thead th:nth-child(2) { width: 18%; }
.cart-table thead th:nth-child(3) { width: 18%; }
.cart-table thead th:nth-child(4) { width: 16%; }
.cart-table thead th:nth-child(5) { width: 8%; }
.cart-table tbody td { padding: 18px; border-bottom: 1px solid #FFE8A8; vertical-align: middle; }
.cart-table tbody tr:last-child td { border-bottom: none; }
.cart-table tbody tr:hover { background: var(--purple-soft, #FFF8E6); }

.cart-product { display: flex; align-items: center; gap: 14px; }
.cart-product-img { width: 70px; height: 70px; border-radius: 10px; object-fit: cover; border: 1px solid #F5E6B8; background: #FFF8E6; }
.cart-product-info h4 { font-size: 13px; font-weight: 600; color: #333; margin-bottom: 3px; line-height: 1.4; }
.cart-product-info h4 a { color: #333; text-decoration: none; }
.cart-product-info h4 a:hover { color: var(--purple-primary, #E5A91A); }
.cart-product-info .cart-author { font-size: 11px; color: #888; }
.cart-product-info .cart-stock { font-size: 11px; color: #16a34a; font-weight: 500; margin-top: 3px; }

.cart-price { font-size: 14px; font-weight: 600; color: var(--purple-dark, #D4A017); }
.cart-price .old-price { display: block; font-size: 11px; color: #bbb; text-decoration: line-through; font-weight: 400; }
.cart-price .discount-tag { display: inline-block; background: #16a34a; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 700; margin-top: 3px; }

/* Quantity Controls */
.cart-qty { display: inline-flex; align-items: center; border: 1.5px solid #F5E6B8; border-radius: 8px; overflow: hidden; }
.cart-qty form { display: inline-flex; margin: 0; padding: 0; }
.cart-qty button { width: 32px; height: 32px; border: none; background: var(--purple-soft, #FFF8E6); cursor: pointer; font-size: 14px; font-weight: 600; color: var(--purple-dark, #D4A017); display: flex; align-items: center; justify-content: center; }
.cart-qty button:hover { background: #D49A0A; color: #1a1a1a; }
.cart-qty span { width: 36px; text-align: center; font-size: 13px; font-weight: 600; color: var(--purple-dark, #D4A017); line-height: 32px; }

.cart-total-price { font-size: 14px; font-weight: 700; color: var(--purple-dark, #D4A017); }
.cart-remove-btn { background: none; border: none; color: #ccc; font-size: 18px; cursor: pointer; transition: color 0.2s; padding: 6px; }
.cart-remove-btn:hover { color: #ef4444; }

.clear-cart-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; background: #fff; border: 1px solid #F5E6B8; border-radius: 8px; font-size: 12px; font-weight: 500; color: #666; cursor: pointer; margin-top: 16px; font-family: 'Poppins', sans-serif; transition: all 0.2s; text-decoration: none; }
.clear-cart-btn:hover { border-color: #ef4444; color: #ef4444; }

/* Order Summary */
.order-summary { background: #fff; border: 1px solid #F5E6B8; border-radius: 14px; padding: 28px; position: sticky; top: 130px; box-shadow: 0 4px 20px rgba(196, 138, 10, 0.06); }
.order-summary h3 { font-size: 16px; font-weight: 700; color: var(--purple-dark, #D4A017); margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #FFE8A8; }
.summary-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; font-size: 13px; }
.summary-row span { color: #666; }
.summary-row strong { color: var(--purple-dark, #D4A017); }
.summary-row.discount strong { color: #16a34a; }
.summary-row.total { border-top: 1.5px solid var(--purple-dark, #D4A017); margin-top: 10px; padding-top: 14px; }
.summary-row.total span { font-size: 15px; font-weight: 600; color: var(--purple-dark, #D4A017); }
.summary-row.total strong { font-size: 20px; font-weight: 800; color: var(--purple-dark, #D4A017); }
.summary-points { font-size: 11px; color: #888; margin-top: 8px; text-align: center; }

.btn-checkout { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 15px; background: #D49A0A; color: #1a1a1a; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; margin-top: 18px; font-family: 'Poppins', sans-serif; transition: all 0.3s; text-decoration: none; }
.btn-checkout:hover { background: #D4A017; box-shadow: 0 6px 20px rgba(229, 169, 26, 0.25); transform: translateY(-1px); }
.btn-checkout i { background: rgba(255,255,255,0.15); padding: 5px 8px; border-radius: 6px; }

.btn-whatsapp-checkout { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 13px; background: #fff; color: #25d366; border: 1.5px solid #25d366; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; margin-top: 10px; font-family: 'Poppins', sans-serif; transition: all 0.3s; text-decoration: none; }
.btn-whatsapp-checkout:hover { background: #25d366; color: #fff; }

/* Promo Code */
.promo-section { margin-top: 22px; padding-top: 18px; border-top: 1px solid #FFE8A8; }
.promo-section h4 { font-size: 13px; font-weight: 600; color: var(--purple-dark, #D4A017); margin-bottom: 10px; }
.promo-input { display: flex; gap: 8px; }
.promo-input input { flex: 1; padding: 10px 14px; border: 1.5px solid #F5E6B8; border-radius: 8px; font-size: 12px; font-family: 'Poppins', sans-serif; outline: none; }
.promo-input input:focus { border-color: var(--purple-primary, #E5A91A); box-shadow: 0 0 0 3px rgba(229, 169, 26, 0.1); }
.promo-input button { padding: 10px 18px; background: var(--purple-lavender, #FFE8A8); color: var(--purple-dark, #D4A017); border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; }
.promo-input button:hover { background: #D49A0A; color: #1a1a1a; }

/* Trust Badges in Summary */
.checkout-trust { margin-top: 22px; padding-top: 18px; border-top: 1px solid #FFE8A8; }
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

/* Empty Cart */
.empty-cart { text-align: center; padding: 60px 20px; }
.empty-cart i { font-size: 4rem; color: #F0D78C; margin-bottom: 16px; }
.empty-cart h3 { color: var(--purple-dark, #D4A017); font-size: 18px; margin-bottom: 8px; }
.empty-cart p { color: #888; font-size: 13px; margin-bottom: 20px; }
.empty-cart a { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #D49A0A; color: #1a1a1a; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; }
.empty-cart a:hover { background: #D4A017; box-shadow: 0 6px 20px rgba(229, 169, 26, 0.25); }

/* Responsive */
@media (max-width: 992px) { .cart-layout { grid-template-columns: 1fr; } .order-summary { position: static; } .cart-suggestions-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) {
.cart-section .container { padding: 0 12px; max-width: 100%; }
.cart-section { padding: 20px 0 40px; }
.cart-table-wrap { max-height: none; overflow-y: visible; border: none; border-radius: 0; background: transparent; }
.cart-table { table-layout: auto; }
.cart-table thead { display: none; }
.cart-table, .cart-table tbody { display: block; width: 100%; }
.cart-table tbody tr { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px; margin-bottom: 12px; background: #fff; border: 1px solid #eee; border-radius: 12px; position: relative; }
.cart-table tbody td { display: block; padding: 0; border: none; }
.cart-table tbody td:first-child { width: 100%; }
.cart-product { gap: 12px; }
.cart-product-img { width: 55px; height: 55px; min-width: 55px; }
.cart-product-info h4 { font-size: 12px; }
.cart-product-info .cart-author { font-size: 10px; }
.cart-table tbody td:nth-child(2) { order: 2; font-size: 13px; }
.cart-table tbody td:nth-child(3) { order: 3; }
.cart-table tbody td:nth-child(4) { order: 4; margin-left: auto; font-size: 13px; font-weight: 700; }
.cart-table tbody td:nth-child(5) { order: 5; position: absolute; top: 12px; right: 12px; }
.cart-qty button { width: 28px; height: 28px; font-size: 12px; }
.cart-qty span { width: 30px; font-size: 12px; line-height: 28px; }
.cart-header { flex-direction: column; align-items: flex-start; gap: 8px; }
.cart-header h1 { font-size: 1.3rem; }
.order-summary { border-radius: 12px; padding: 20px; }
.btn-checkout { font-size: 13px; padding: 13px; }
.btn-whatsapp-checkout { font-size: 12px; padding: 11px; }
.promo-input { flex-direction: column; }
.promo-input input { width: 100%; }
.promo-input button { width: 100%; }
.cart-suggestions { display: none; }
.clear-cart-btn { font-size: 11px; padding: 8px 14px; }
}
@media (max-width: 480px) {
.cart-section .container { padding: 0 8px; }
.cart-product-img { width: 48px; height: 48px; min-width: 48px; }
.cart-product-info h4 { font-size: 11px; }
.cart-price { font-size: 12px; }
.cart-total-price { font-size: 12px; }
.order-summary { padding: 16px; }
.order-summary h3 { font-size: 14px; }
.summary-row { font-size: 12px; }
.summary-row.total strong { font-size: 17px; }
}

/* You May Also Like - Cart Page */
.cart-suggestions { padding: 0 0 60px; }
.cart-suggestions-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
.cart-suggestions-header h2 { font-size: 1.3rem; font-weight: 700; color: var(--purple-dark, #D4A017); }
.cart-view-all { font-size: 13px; font-weight: 600; color: #E5A91A; text-decoration: none; display: flex; align-items: center; gap: 5px; }
.cart-view-all:hover { color: #D4A017; }
.cart-suggestions-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; }
.cart-suggest-card { background: #fff; border: 1px solid #F5E6B8; border-radius: 12px; overflow: hidden; transition: all 0.25s; }
.cart-suggest-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(196, 138, 10, 0.1); border-color: #F0D78C; }
.cart-suggest-link { text-decoration: none; color: inherit; display: block; }
.cart-suggest-img { position: relative; width: 100%; height: 160px; background: #FFF8E6; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.cart-suggest-img img { width: 100%; height: 100%; object-fit: cover; }
.cart-suggest-badge { position: absolute; top: 10px; left: 10px; padding: 3px 8px; border-radius: 4px; font-size: 9px; font-weight: 700; color: #fff; }
.cart-suggest-badge.bestseller { background: #D49A0A; }
.cart-suggest-badge.discount { background: #16a34a; }
.cart-suggest-body { padding: 14px; }
.cart-suggest-body h4 { font-size: 12px; font-weight: 600; color: #333; margin-bottom: 6px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.cart-suggest-stars { color: #f5a623; font-size: 10px; margin-bottom: 8px; }
.cart-suggest-price { display: flex; align-items: baseline; gap: 8px; }
.cart-suggest-price .current { font-size: 14px; font-weight: 700; color: var(--purple-dark, #D4A017); }
.cart-suggest-price .old { font-size: 11px; color: #bbb; text-decoration: line-through; }
.cart-suggest-action { padding: 0 14px 14px; }
.cart-suggest-btn { display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; padding: 9px; background: #fff; color: var(--purple-primary, #E5A91A); border: 1.5px solid #F5E6B8; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.cart-suggest-btn:hover { background: #D49A0A; color: #1a1a1a; border-color: #E5A91A; }
</style>

<!-- Breadcrumb -->
<div class="product-page-breadcrumb">
    <div class="container">
        <a href="<?php echo BASE_URL; ?>">Home</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <span>Cart</span>
    </div>
</div>

<section class="cart-section">
    <div class="container">
        <div class="cart-header">
            <h1>Your Cart <span>(<?php echo $cartCount; ?> Item<?php echo $cartCount !== 1 ? 's' : ''; ?>)</span></h1>
            <a href="<?php echo BASE_URL; ?>shop.php" class="continue-shopping">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
        </div>

        <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added any products yet.</p>
            <a href="<?php echo BASE_URL; ?>shop.php"><i class="fas fa-store"></i> Browse Products</a>
        </div>
        <?php else: ?>
        <div class="cart-layout">
            <!-- Cart Items -->
            <div>
                <div class="cart-table-wrap">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item):
                                $variationId = (int)($item['variation_id'] ?? 0);
                                $cartLineKey = $item['product_id'] . '_' . $variationId;
                                $itemTotal = $item['price'] * $item['quantity'];
                                $discount = ($item['original_price'] && $item['original_price'] > $item['price']) 
                                    ? round((($item['original_price'] - $item['price']) / $item['original_price']) * 100) : 0;
                            ?>
                            <tr>
                                <td>
                                    <div class="cart-product">
                                        <?php if ($item['image']): ?>
                                        <img src="<?php echo htmlspecialchars(media_image_url($item['image'])); ?>" alt="" class="cart-product-img">
                                        <?php else: ?>
                                        <div class="cart-product-img" style="display:flex; align-items:center; justify-content:center; color:#ccc; font-size:1.5rem;"><i class="fas fa-tshirt"></i></div>
                                        <?php endif; ?>
                                        <div class="cart-product-info">
                                            <h4><a href="<?php echo BASE_URL; ?>product.php?slug=<?php echo htmlspecialchars($item['slug']); ?>"><?php echo htmlspecialchars($item['name']); ?></a></h4>
                                            <?php if (!empty($item['variation_label'])): ?>
                                            <span class="cart-author" style="color:#E5A91A; font-weight:600;"><?php echo htmlspecialchars($item['variation_label']); ?></span>
                                            <?php endif; ?>
                                            <span class="cart-author">by <?php echo htmlspecialchars($item['author']); ?></span>
                                            <div class="cart-stock"><i class="fas fa-check-circle"></i> In Stock</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cart-price" data-price-id="<?php echo $cartLineKey; ?>" data-price="<?php echo $item['price']; ?>">
                                        <?php echo format_price($item['price']); ?>
                                        <?php if ($item['original_price'] && $item['original_price'] > $item['price']): ?>
                                        <span class="old-price"><?php echo format_price($item['original_price']); ?></span>
                                        <span class="discount-tag"><?php echo $discount; ?>% OFF</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="cart-qty">
                                        <button type="button" onclick="updateCartQty(<?php echo $item['product_id']; ?>, <?php echo $variationId; ?>, parseInt(document.getElementById('qty-<?php echo $cartLineKey; ?>').value) - 1)">-</button>
                                        <input type="number" id="qty-<?php echo $cartLineKey; ?>" value="<?php echo $item['quantity']; ?>" min="1" readonly style="width:35px; border:none; text-align:center; font-weight:700; font-size:14px; background:transparent;">
                                        <button type="button" onclick="updateCartQty(<?php echo $item['product_id']; ?>, <?php echo $variationId; ?>, parseInt(document.getElementById('qty-<?php echo $cartLineKey; ?>').value) + 1)">+</button>
                                    </div>
                                </td>
                                <td>
                                    <span class="cart-total-price" data-total-id="<?php echo $cartLineKey; ?>"><?php echo format_price($itemTotal); ?></span>
                                </td>
                                <td>
                                    <form action="<?php echo BASE_URL; ?>controllers/CartController.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <input type="hidden" name="variation_id" value="<?php echo $variationId; ?>">
                                        <button type="submit" class="cart-remove-btn" title="Remove"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <form action="<?php echo BASE_URL; ?>controllers/CartController.php" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="clear-cart-btn" onclick="return confirm('Clear all items from cart?')">
                        <i class="fas fa-trash"></i> Clear Cart
                    </button>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal (<?php echo $cartCount; ?> Items)</span>
                    <strong class="cart-subtotal-value"><?php echo format_price($subtotal); ?></strong>
                </div>
                <?php if ($discount > 0): ?>
                <div class="summary-row discount">
                    <span>Discount</span>
                    <strong>- <?php echo format_price($discount); ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($deliveryFee === 0): ?>
                <div class="summary-row discount">
                    <span>Delivery Fee</span>
                    <strong class="cart-delivery-value">FREE</strong>
                </div>
                <?php else: ?>
                <div class="summary-row">
                    <span>Delivery Fee</span>
                    <strong class="cart-delivery-value"><?php echo format_price($deliveryFee); ?></strong>
                </div>
                <?php endif; ?>
                <p style="font-size:10px; color:#888; margin:-4px 0 8px;">Sri Lanka estimate. International shipping is calculated at checkout.</p>
                <div class="summary-row total">
                    <span>Total</span>
                    <strong class="cart-total-value"><?php echo format_price($total); ?></strong>
                </div>

                <a href="<?php echo BASE_URL; ?>checkout.php" class="btn-checkout">
                    Proceed to Checkout <i class="fas fa-arrow-right"></i>
                </a>

                <div class="promo-section">
                    <h4>Have a Promo Code?</h4>
                    <?php if ($couponMessage): ?>
                    <div style="background:#ecfdf5; color:#16a34a; padding:8px 12px; border-radius:6px; font-size:11px; margin-bottom:10px;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($couponMessage); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($couponError): ?>
                    <div style="background:#fef2f2; color:#dc2626; padding:8px 12px; border-radius:6px; font-size:11px; margin-bottom:10px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($couponError); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($appliedCode): ?>
                    <div style="display:flex; align-items:center; justify-content:space-between; background:var(--purple-lavender, #FFE8A8); padding:10px 14px; border-radius:8px; border:1px solid #F5E6B8;">
                        <span style="font-size:12px; font-weight:600; color:var(--purple-dark, #D4A017);"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($appliedCode); ?></span>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="coupon_action" value="remove">
                            <button type="submit" style="background:none; border:none; color:#ef4444; font-size:12px; cursor:pointer; font-weight:500;">Remove</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="coupon_action" value="apply">
                        <div class="promo-input">
                            <input type="text" name="promo_code" placeholder="Enter promo code" style="text-transform:uppercase;">
                            <button type="submit">Apply</button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>

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
        <?php endif; ?>
    </div>
</section>

<!-- Hidden form for quantity updates -->
<form id="cartUpdateForm" action="<?php echo BASE_URL; ?>controllers/CartController.php" method="POST" style="display:none;">
    <input type="hidden" name="action" value="update" id="cartUpdateAction">
    <input type="hidden" name="product_id" value="" id="cartUpdateProductId">
    <input type="hidden" name="variation_id" value="0" id="cartUpdateVariationId">
    <input type="hidden" name="quantity" value="" id="cartUpdateQuantity">
</form>

<!-- You May Also Like -->
<?php if (!empty($featuredProducts)): ?>
<section class="cart-suggestions">
    <div class="container">
        <div class="cart-suggestions-header">
            <h2>You May Also Like</h2>
            <a href="<?php echo BASE_URL; ?>shop.php" class="cart-view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="cart-suggestions-grid">
            <?php foreach ($featuredProducts as $fp): 
                $fpDiscount = ($fp['original_price'] && $fp['original_price'] > $fp['price']) ? round((($fp['original_price'] - $fp['price']) / $fp['original_price']) * 100) : 0;
            ?>
            <div class="cart-suggest-card">
                <a href="<?php echo BASE_URL; ?>product.php?slug=<?php echo htmlspecialchars($fp['slug']); ?>" class="cart-suggest-link">
                    <div class="cart-suggest-img">
                        <?php if ($fp['image']): ?>
                        <img src="<?php echo htmlspecialchars(media_image_url($fp['image'])); ?>" alt="">
                        <?php else: ?>
                        <i class="fas fa-tshirt" style="font-size:2.5rem; color:#ddd;"></i>
                        <?php endif; ?>
                        <?php if ($fp['featured']): ?>
                        <span class="cart-suggest-badge bestseller">Bestseller</span>
                        <?php elseif ($fpDiscount >= 10): ?>
                        <span class="cart-suggest-badge discount"><?php echo $fpDiscount; ?>% OFF</span>
                        <?php endif; ?>
                    </div>
                    <div class="cart-suggest-body">
                        <h4><?php echo htmlspecialchars($fp['name']); ?></h4>
                        <div class="cart-suggest-stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                        </div>
                        <div class="cart-suggest-price">
                            <span class="current"><?php echo format_price($fp['price']); ?></span>
                            <?php if ($fp['original_price'] && $fp['original_price'] > $fp['price']): ?>
                            <span class="old"><?php echo format_price($fp['original_price']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <div class="cart-suggest-action">
                    <form action="<?php echo BASE_URL; ?>controllers/CartController.php" method="POST">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $fp['id']; ?>">
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="redirect_url" value="<?php echo BASE_URL; ?>cart.php">
                        <button type="submit" class="cart-suggest-btn"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
function updateCartQty(productId, variationId, newQty) {
    if (newQty < 1) newQty = 1;
    variationId = variationId || 0;
    var lineKey = productId + '_' + variationId;
    
    fetch('<?php echo BASE_URL; ?>controllers/CartController.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=update&product_id=' + productId + '&variation_id=' + variationId + '&quantity=' + newQty + '&ajax=1'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            var qtyInput = document.getElementById('qty-' + lineKey);
            if (qtyInput) qtyInput.value = newQty;
            
            var priceEl = document.querySelector('[data-price-id="' + lineKey + '"]');
            var totalEl = document.querySelector('[data-total-id="' + lineKey + '"]');
            if (priceEl && totalEl) {
                var price = parseFloat(priceEl.getAttribute('data-price'));
                totalEl.textContent = typeof formatStorePrice === 'function' ? formatStorePrice(price * newQty) : ('LKR ' + (price * newQty).toLocaleString());
            }
            
            // Update summary with server-calculated values
            document.querySelectorAll('.cart-subtotal-value').forEach(function(el) {
                el.textContent = typeof formatStorePrice === 'function' ? formatStorePrice(data.subtotal) : ('LKR ' + Number(data.subtotal).toLocaleString());
            });
            document.querySelectorAll('.cart-delivery-value').forEach(function(el) {
                el.textContent = data.delivery_fee === 0 ? 'FREE' : (typeof formatStorePrice === 'function' ? formatStorePrice(data.delivery_fee) : ('LKR ' + Number(data.delivery_fee).toLocaleString()));
            });
            document.querySelectorAll('.cart-total-value').forEach(function(el) {
                el.textContent = typeof formatStorePrice === 'function' ? formatStorePrice(data.total) : ('LKR ' + Number(data.total).toLocaleString());
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
