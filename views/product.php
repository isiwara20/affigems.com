<?php
/**
 * Public Product Detail Page
 * URL: /product.php?slug=product-slug
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/ProductDAL.php';
require_once __DIR__ . '/../dal/CategoryDAL.php';
require_once __DIR__ . '/../dal/ReviewDAL.php';
require_once __DIR__ . '/../dal/ProductVariationDAL.php';
require_once __DIR__ . '/../helpers/sms.php';

$warrantyHelper = __DIR__ . '/../helpers/warranty.php';
if (is_file($warrantyHelper)) {
    require_once $warrantyHelper;
}
if (!function_exists('ezymart_warranty_period_label')) {
    function ezymart_warranty_period_label(?string $period): string
    {
        $map = [
            '' => 'No warranty',
            '3_months' => '3 Months',
            '6_months' => '6 Months',
            '1_year' => '1 Year',
            '2_years' => '2 Years',
            '3_years' => '3 Years',
        ];
        $period = trim((string)$period);
        return $map[$period] ?? 'No warranty';
    }
}
// Get product by slug
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('Location: ' . BASE_URL);
    exit;
}

try {
    $productDAL = new ProductDAL();
    $categoryDAL = new CategoryDAL();
    
    $product = $productDAL->getBySlug($slug);
    
    if (!$product || $product['status'] !== 'active') {
        header('Location: ' . BASE_URL);
        exit;
    }
    
    // Get related products
    $relatedProducts = $productDAL->getRelated($product['category_id'], $product['id'], 8);
    
    // Get all active categories for sidebar
    $categories = $categoryDAL->getActive();

    // Get reviews
    $reviewDAL = new ReviewDAL();
    $reviews = $reviewDAL->getByProduct($product['id']);
    $ratingData = $reviewDAL->getAverageRating($product['id']);
    $avgRating = $ratingData['avg_rating'] ? round($ratingData['avg_rating'], 1) : 0;
    $totalReviews = (int)$ratingData['total'];

    $variationDAL = new ProductVariationDAL();
    $variationData = $variationDAL->getProductVariationData((int)$product['id']);
    $hasVariations = !empty($variationData['has_variations']);
    $canPurchase = $hasVariations
        ? count(array_filter($variationData['variations'], static function ($v) { return (int)$v['stock'] > 0; })) > 0
        : (int)$product['stock'] > 0;
    
} catch (Exception $e) {
    header('Location: ' . BASE_URL);
    exit;
}

// Calculate discount percentage
$discountPercent = 0;
if ($product['original_price'] && $product['original_price'] > $product['price']) {
    $discountPercent = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
}

$restockUserEmail = '';
$restockUserPhone = '';
$restockUserLoggedIn = !empty($_SESSION['logged_in']) && empty($_SESSION['is_admin']);
$restockAlreadyWaiting = false;
if ($restockUserLoggedIn) {
    $restockUserEmail = (string)($_SESSION['user_email'] ?? '');
    if ($restockUserEmail !== '') {
        try {
            require_once __DIR__ . '/../dal/UserDAL.php';
            $restockUser = (new UserDAL())->findByEmail($restockUserEmail);
            $restockUserPhone = (string)($restockUser['phone'] ?? '');
        } catch (Throwable $e) {
            $restockUserPhone = '';
        }
    }
}

if ($restockUserLoggedIn && !$canPurchase && $restockUserEmail !== '') {
    try {
        require_once __DIR__ . '/../dal/RestockNotifyDAL.php';
        $restockAlreadyWaiting = (new RestockNotifyDAL())->isSubscribed((int)$product['id'], $restockUserEmail, '');
    } catch (Throwable $e) {
        $restockAlreadyWaiting = false;
    }
}

require_once __DIR__ . '/../helpers/seo.php';

$pageTitle = ezymart_product_page_title($product['name']);
$bodyClass = 'product-page';

$productShareUrl = absolute_url('product.php?slug=' . urlencode($product['slug']));
$productShareImage = !empty($product['image'])
    ? absolute_asset_image_url($product['image'])
    : absolute_asset_image_url('assets/images/logo.png');
$productSharePrice = format_price($product['price']);
$pageMetaDescription = ezymart_product_meta_description($product['name']);
$pageOg = [
    'title' => ezymart_product_page_title($product['name']),
    'description' => $pageMetaDescription,
    'image' => $productShareImage,
    'url' => $productShareUrl,
    'type' => 'product',
    'price' => number_format((float)$product['price'], 2, '.', ''),
    'currency' => 'LKR',
];
$productShareMessage = $product['name'] . ' — ' . $productSharePrice . ' ' . $productShareUrl;
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<?php if (!empty($_SESSION['cart_error'])): ?>
<div class="container" style="padding-top:16px;">
    <div class="alert alert-error" style="position:static; max-width:none; width:auto; animation:none;">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($_SESSION['cart_error']); unset($_SESSION['cart_error']); ?>
    </div>
</div>
<?php endif; ?>

<style>
/* Product Page - Professional UI */
.product-page-breadcrumb {
    background: #f8f9fb;
    padding: 14px 0;
    border-bottom: 1px solid #e8ecf5;
}
.product-page-breadcrumb .container {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #888;
}
.product-page-breadcrumb a {
    color: #002868;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}
.product-page-breadcrumb a:hover { color: #002868; }
.product-page-breadcrumb .separator { color: #ccc; font-size: 10px; }

/* Product Section */
.product-section { padding: 35px 0 60px; background: #fff; }
.product-grid {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 45px;
    margin-bottom: 50px;
    align-items: start;
}
.product-image-wrap {
    background: #f8f9fb;
    border-radius: 16px;
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 440px;
    border: 1px solid #e8ecf5;
    overflow: hidden;
    position: relative;
    cursor: zoom-in;
    transition: box-shadow 0.3s;
}
.product-image-wrap:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.05);
}
.product-image-wrap img {
    width: 100%;
    height: 100%;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    object-position: center;
    transition: transform 0.3s ease;
}
.product-image-wrap.zoomed {
    cursor: zoom-out;
}
.product-image-wrap.zoomed img {
    transform: scale(2);
    cursor: zoom-out;
}
.product-image-placeholder {
    width: 100%;
    height: 380px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ddd;
    font-size: 5rem;
}

/* Image Thumbnails */
.product-image-section {
    position: sticky;
    top: 120px;
}
.product-thumbnails {
    display: flex;
    gap: 10px;
    margin-top: 14px;
    overflow-x: auto;
    padding-bottom: 4px;
}
.product-thumbnails::-webkit-scrollbar {
    height: 4px;
}
.product-thumbnails::-webkit-scrollbar-thumb {
    background: #ddd;
    border-radius: 4px;
}
.thumb {
    width: 65px;
    height: 65px;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid #e8ecf5;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.2s;
}
.thumb:hover {
    border-color: #003b94;
}
.thumb.active {
    border-color: #002868;
    box-shadow: 0 2px 8px rgba(0,82,212,0.15);
}
.thumb img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    background: #fff;
}

/* Product Info */
.product-info { padding: 5px 0; }
.product-category-badge {
    display: inline-block;
    background: #002868;
    color: #fff;
    padding: 5px 14px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 14px;
}
.product-title {
    font-size: 1.65rem;
    font-weight: 700;
    color: #002868;
    margin-bottom: 8px;
    line-height: 1.35;
}
.product-author {
    color: #777;
    font-size: 14px;
    margin-bottom: 14px;
}
.product-author span { color: #002868; font-weight: 600; }
.product-heading-top {
    display: none;
    margin-bottom: 18px;
}
.product-rating {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}
.product-rating .stars { color: #c9a96e; font-size: 15px; }
.product-rating .rating-text { color: #666; font-size: 13px; }
.product-rating .rating-text strong { color: #002868; }

/* Price */
.product-price-section {
    display: flex;
    align-items: baseline;
    gap: 14px;
    margin-bottom: 18px;
    padding: 20px 0;
    border-top: 1px solid #f0f4ff;
    border-bottom: 1px solid #f0f4ff;
}
.product-price { font-size: 1.9rem; font-weight: 800; color: #002868; }
.product-original-price { font-size: 1.1rem; color: #bbb; text-decoration: line-through; }
.product-discount-badge {
    background: #002868;
    color: #fff;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
}

/* Product Share */
.product-share {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    padding: 12px 14px;
    background: #f8f9fb;
    border: 1px solid #e8ecf5;
    border-radius: 12px;
}
.product-share-label {
    font-size: 13px;
    font-weight: 600;
    color: #002868;
    white-space: nowrap;
}
.product-share-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* Product Variations */
.product-variations { margin: 18px 0 8px; }
.product-variation-group { margin-bottom: 16px; }
.product-variation-label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #002868;
    margin-bottom: 8px;
}
.product-variation-options {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.product-variation-btn {
    border: 1.5px solid #dbe5f5;
    background: #fff;
    color: #333;
    border-radius: 10px;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'Poppins', sans-serif;
}
.product-variation-btn:hover { border-color: #003b94; color: #003b94; }
.product-variation-btn.is-active {
    background: #001f52;
    border-color: #003b94;
    color: #fff;
}
.product-variation-btn.is-disabled {
    opacity: 0.45;
    cursor: not-allowed;
    text-decoration: line-through;
}
.product-variation-btn.has-color-swatch {
    position: relative;
    padding-left: 38px;
}
.product-variation-btn.has-color-swatch::before {
    content: '';
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--swatch-color, #ccc);
    border: 2px solid rgba(255,255,255,0.95);
    box-shadow: 0 0 0 1px rgba(0,0,0,0.08);
}
.product-variation-selected {
    font-size: 12px;
    color: #666;
    margin-top: 6px;
}
.product-variation-hint {
    font-size: 12px;
    color: #003b94;
    margin-top: 8px;
}

.share-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 15px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    font-family: 'Poppins', sans-serif;
}
.share-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.share-fb { background: #1877f2; }
.share-wa { background: #25d366; }
.share-tw { background: #000; }
.share-tg { background: #229ed9; }
.share-pin { background: #e60023; }
.share-copy {
    background: #001f52;
    width: auto;
    padding: 0 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    gap: 6px;
}
.share-copy.is-copied {
    background: #16a34a;
}

/* Description text below price */
.product-short-desc {
    font-size: 13px;
    color: #666;
    line-height: 1.7;
    margin-bottom: 20px;
}

/* Stock */
.stock-status { display: flex; align-items: center; gap: 8px; margin-bottom: 22px; font-size: 13px; font-weight: 500; }
.stock-status.in-stock { color: #16a34a; }
.stock-status.low-stock { color: #002868; }
.stock-status.out-of-stock { color: #dc3545; }

/* Restock notify */
.restock-notify {
    margin-bottom: 24px;
    padding: 20px;
    border: 1px solid #e8ecf5;
    border-radius: 14px;
    background: linear-gradient(180deg, #f8fbff 0%, #fff 100%);
}
.restock-notify[hidden] { display: none !important; }
.restock-notify__title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 8px;
    font-size: 15px;
    font-weight: 700;
    color: #0d1137;
}
.restock-notify__title i { color: #003b94; }
.restock-notify__desc {
    margin: 0 0 16px;
    font-size: 13px;
    line-height: 1.6;
    color: #666;
}
.restock-notify__fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 14px;
}
.restock-notify__field label {
    display: block;
    margin-bottom: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #002868;
}
.restock-notify__field input {
    width: 100%;
    padding: 11px 12px;
    border: 1.5px solid #e4e8f0;
    border-radius: 10px;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    color: #0d1137;
    background: #fff;
}
.restock-notify__field input:focus {
    outline: none;
    border-color: #003b94;
    box-shadow: 0 0 0 3px rgba(0, 40, 104, 0.08);
}
.restock-notify__submit {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 18px;
    border: none;
    border-radius: 10px;
    background: #001f52;
    color: #fff;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.restock-notify__submit:hover { background: #002868; }
.restock-notify__submit:disabled { opacity: 0.7; cursor: not-allowed; }
.restock-notify__message {
    margin-top: 12px;
    font-size: 12px;
    line-height: 1.5;
    font-weight: 500;
}
.restock-notify__message.is-success { color: #16a34a; }
.restock-notify__message.is-error { color: #dc3545; }
.restock-notify__waiting {
    text-align: center;
    padding: 8px 4px 4px;
}
.restock-notify__waiting[hidden] { display: none !important; }
.restock-notify__waiting-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 14px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 40, 104, 0.1);
    color: #003b94;
    font-size: 22px;
    animation: restockWaitingPulse 2.4s ease-in-out infinite;
}
.restock-notify__hourglass {
    display: inline-block;
    transform-origin: center center;
    animation: restockHourglassFlip 2.4s ease-in-out infinite;
}
@keyframes restockHourglassFlip {
    0%, 42% { transform: rotate(0deg); }
    50%, 92% { transform: rotate(180deg); }
    100% { transform: rotate(360deg); }
}
@keyframes restockWaitingPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(0, 40, 104, 0.18); }
    50% { box-shadow: 0 0 0 10px rgba(0, 40, 104, 0); }
}
.restock-notify__waiting-title {
    margin: 0 0 10px;
    font-size: 16px;
    font-weight: 700;
    color: #0d1137;
}
.restock-notify__waiting-text {
    margin: 0 0 18px;
    font-size: 13px;
    line-height: 1.7;
    color: #555;
    max-width: 520px;
    margin-left: auto;
    margin-right: auto;
}
.restock-notify__waiting-details {
    display: grid;
    gap: 10px;
    max-width: 420px;
    margin: 0 auto 16px;
    text-align: left;
    background: #fff;
    border: 1px solid #e8ecf5;
    border-radius: 12px;
    padding: 14px 16px;
}
.restock-notify__waiting-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 12px;
}
.restock-notify__waiting-row span {
    color: #888;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.restock-notify__waiting-row strong {
    color: #0d1137;
    font-weight: 600;
    text-align: right;
    word-break: break-word;
}
.restock-notify__waiting-note {
    margin: 0;
    font-size: 12px;
    line-height: 1.6;
    color: #16a34a;
    font-weight: 500;
}
.restock-notify__back-btn {
    margin-top: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border: 1px solid #d7e3f8;
    border-radius: 10px;
    background: #fff;
    color: #003b94;
    font-family: 'Poppins', sans-serif;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.restock-notify__back-btn:hover {
    background: #f4f8ff;
    border-color: #003b94;
}
.restock-notify.is-waiting {
    border-color: rgba(0, 40, 104, 0.18);
    background: linear-gradient(180deg, #f4f8ff 0%, #fff 100%);
}
@media (max-width: 640px) {
    .restock-notify__fields { grid-template-columns: 1fr; }
}

/* Feature Icons Row */
.product-features-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 26px;
}
.feature-item {
    text-align: center;
    padding: 14px 8px;
    border: 1px solid #e8ecf5;
    border-radius: 10px;
    background: #f8f9fb;
    transition: all 0.2s;
}
.feature-item:hover {
    border-color: #003b94;
    background: #fff;
}
.feature-item i { font-size: 1.1rem; color: #003b94; margin-bottom: 6px; display: block; }
.feature-item span { font-size: 10px; color: #555; font-weight: 600; display: block; letter-spacing: 0.2px; }

/* Quantity */
.quantity-section { display: flex; align-items: center; gap: 16px; margin-bottom: 22px; }
.quantity-label { font-weight: 600; color: #002868; font-size: 14px; }
.quantity-selector {
    display: flex;
    align-items: center;
    border: 1.5px solid #e4e8f0;
    border-radius: 10px;
    overflow: hidden;
}
.qty-btn {
    width: 40px; height: 40px;
    border: none; background: #f8f9fb;
    cursor: pointer; font-size: 1rem; font-weight: 600; color: #333;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
}
.qty-btn:hover { background: #001f52; color: #ffffff; }
.qty-input {
    width: 50px; height: 40px;
    border: none; border-left: 1px solid #e8ecf5; border-right: 1px solid #e8ecf5;
    text-align: center; font-size: 14px; font-weight: 700; color: #002868;
    background: #fff;
}
.qty-input:focus { outline: none; }

/* Action Buttons */
.product-actions { display: flex; gap: 14px; margin-bottom: 24px; }
.product-sticky-actions { display: none; }
.btn-add-cart {
    flex: 1; padding: 15px 24px;
    background: #001f52; color: #ffffff; border: none; border-radius: 50px;
    font-size: 14px; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 10px;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s;
}
.btn-add-cart:hover { background: #002868; box-shadow: 0 6px 20px rgba(0,40,104,0.25); transform: translateY(-1px); }
.btn-buy-now {
    flex: 1; padding: 15px 24px;
    background: #fff; color: #003b94; border: 2px solid #003b94; border-radius: 50px;
    font-size: 14px; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 10px;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s;
}
.btn-buy-now:hover { background: #001f52; color: #ffffff; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,82,212,0.3); }

/* Trust Badges */
.trust-badges {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 0;
    border: 1px solid #e8ecf5; border-radius: 12px; overflow: hidden;
    margin-bottom: 10px; background: #f8f9fb;
}
.trust-badge {
    text-align: center; padding: 16px 8px;
    border-right: 1px solid #e8ecf5;
    transition: background 0.2s;
}
.trust-badge:last-child { border-right: none; }
.trust-badge:hover { background: #fff; }
.trust-badge i { font-size: 1.2rem; color: #002868; margin-bottom: 6px; display: block; }
.trust-badge span { font-size: 10px; color: #555; font-weight: 600; display: block; }

.product-wishlist-form { margin-bottom: 20px; }

.product-wishlist-btn {
    width: 100%;
    padding: 11px;
    background: #fff;
    color: #666;
    border: 1.5px solid #eee;
    border-radius: 10px;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.product-wishlist-btn.is-active {
    background: #f0f4ff;
    color: #ef4444;
    border-color: #fecaca;
}

.product-wishlist-btn:hover {
    border-color: #fecaca;
    color: #ef4444;
}

.product-wishlist-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

/* Product Details Section */
.product-details-section {
    display: grid; grid-template-columns: 240px 1fr; gap: 30px; margin-bottom: 60px;
}
.product-sidebar { position: sticky; top: 120px; align-self: start; }
.sidebar-widget {
    background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 20px; margin-bottom: 20px;
}
.sidebar-widget h3 {
    font-size: 14px; font-weight: 700; color: #002868;
    margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid #002868;
}
.category-list { list-style: none; padding: 0; margin: 0; }
.category-list li a {
    display: block; padding: 9px 12px; color: #444; text-decoration: none;
    border-radius: 6px; font-size: 13px; font-weight: 500;
    transition: all 0.2s; border-left: 3px solid transparent; margin-bottom: 1px;
}
.category-list li a:hover { background: #fafafa; color: #002868; border-left-color: #002868; }
.category-list li a.active { background: #f0f4ff; color: #002868; font-weight: 600; border-left-color: #002868; }

/* Tabs */
.product-tabs { background: #fff; border: 1px solid #eee; border-radius: 12px; overflow: hidden; min-height: 450px; }
.tabs-header { display: flex; border-bottom: 1px solid #eee; background: #fafafa; }
.tab-btn {
    padding: 14px 26px; border: none; background: none; cursor: pointer;
    font-size: 13px; font-weight: 500; color: #777; position: relative;
    font-family: 'Poppins', sans-serif; transition: color 0.2s;
}
.tab-btn:hover { color: #002868; }
.tab-btn.active { color: #002868; font-weight: 600; background: #fff; }
.tab-btn.active::after {
    content: ''; position: absolute; bottom: -1px; left: 0; right: 0;
    height: 2px; background: #002868;
}
.tab-content { padding: 28px; display: none; height: 400px; overflow-y: auto; }
.tab-content.active { display: block; }
.tab-content p { color: #555; line-height: 1.8; margin-bottom: 14px; font-size: 13px; }

/* Reviews - Professional UI */
.review-summary { margin-bottom: 20px; padding: 18px; background: #f8f9fb; border-radius: 10px; border: 1px solid #eee; }
.review-avg { display: flex; align-items: center; gap: 16px; }
.avg-number { font-size: 2.6rem; font-weight: 800; color: #002868; line-height: 1; }
.review-count { font-size: 12px; color: #888; display: block; margin-top: 4px; }
.reviews-list { margin-bottom: 20px; }
.no-reviews { color: #999; font-size: 13px; text-align: center; padding: 40px 20px; }
.review-item { padding: 14px 16px; border: 1px solid #f0f0f0; border-radius: 10px; margin-bottom: 8px; background: #fff; }
.review-item:hover { border-color: #e0e0e0; }
.review-header { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
.reviewer-avatar { width: 34px; height: 34px; border-radius: 50%; background: #002868; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
.reviewer-info { flex: 1; }
.reviewer-info strong { font-size: 13px; color: #002868; display: block; margin-bottom: 1px; }
.reviewer-info .review-stars { display: flex; gap: 1px; }
.review-date { font-size: 10px; color: #bbb; white-space: nowrap; }
.review-comment { font-size: 12px; color: #555; line-height: 1.6; padding-left: 44px; }
/* Review Form */
.add-review-form { background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 18px; margin-top: 12px; }
.add-review-form h4 { font-size: 13px; font-weight: 600; color: #002868; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0; }
.rating-input { margin-bottom: 12px; }
.rating-input > label { display: block; font-size: 12px; font-weight: 600; color: #333; margin-bottom: 6px; }
.star-rating-input { display: flex; flex-direction: row-reverse; gap: 2px; justify-content: flex-end; }
.star-rating-input input { display: none; }
.star-rating-input label { cursor: pointer; font-size: 1.3rem; color: #ddd; transition: color 0.15s; margin-bottom: 0; }
.star-rating-input input:checked ~ label, .star-rating-input label:hover, .star-rating-input label:hover ~ label { color: #c9a96e; }
.review-textarea { margin-bottom: 12px; }
.review-textarea label { display: block; font-size: 12px; font-weight: 600; color: #333; margin-bottom: 6px; }
.review-textarea textarea { width: 100%; padding: 10px 12px; border: 1.5px solid #e4e8f0; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 12px; resize: vertical; outline: none; min-height: 60px; background: #fafafa; transition: all 0.3s; }
.review-textarea textarea:focus { border-color: #002868; background: #fff; }
.btn-submit-review { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; background: #002868; color: #fff; border: none; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-submit-review:hover { background: #001f52; }
.login-to-review { background: #f8f9fb; padding: 18px; border-radius: 10px; text-align: center; margin-top: 12px; border: 1px solid #eee; }
.login-to-review p { font-size: 12px; color: #666; margin: 0; }
.login-to-review a { color: #002868; font-weight: 600; text-decoration: none; }

/* Author Section */
.author-section {
    background: #f8f9fb; border: 1px solid #eee; border-radius: 14px;
    padding: 35px 40px; display: flex; align-items: center; justify-content: space-between; gap: 40px; margin-bottom: 50px;
}
.author-left { display: flex; align-items: center; gap: 24px; }
.author-details { }
.author-label { font-size: 11px; color: #999; display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
.author-details h4 { font-size: 18px; font-weight: 700; color: #002868; margin-bottom: 3px; }
.author-role { font-size: 13px; font-weight: 600; color: #444; margin-bottom: 8px; }
.author-exp { font-size: 12px; color: #666; line-height: 1.7; margin-bottom: 14px; }
.author-link {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 12px; font-weight: 600; color: #002868; text-decoration: none;
    padding: 9px 18px; border: 1.5px solid #002868; border-radius: 50px;
    transition: all 0.3s;
}
.author-link:hover { background: #002868; color: #fff; }
.author-stats { display: flex; gap: 30px; }
.author-stats-col { display: flex; flex-direction: column; gap: 16px; }
.author-stat-item { display: flex; align-items: center; gap: 14px; }
.author-stat-icon {
    width: 44px; height: 44px; border-radius: 50%; border: 1.5px solid #ddd;
    display: flex; align-items: center; justify-content: center;
    color: #555; font-size: 16px; flex-shrink: 0; background: #fff;
}
.author-stat-item strong { display: block; font-size: 18px; font-weight: 700; color: #002868; }
.author-stat-item span { display: block; font-size: 11px; color: #888; }

/* Related Products */
.related-section { margin-bottom: 60px; }
.related-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
.related-header h2 { font-size: 1.3rem; font-weight: 700; color: #002868; }
.related-view-all { font-size: 13px; font-weight: 600; color: #002868; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: gap 0.2s; }
.related-view-all:hover { gap: 10px; }
.related-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
.related-card { background: #fff; border: 1px solid #eee; border-radius: 12px; overflow: hidden; transition: all 0.25s; text-decoration: none; color: inherit; display: block; }
.related-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.07); }
.related-card-img { width: 100%; height: 170px; background: #f8f8f8; display: flex; align-items: center; justify-content: center; }
.related-card-img img { width: 100%; height: 100%; object-fit: cover; }
.related-card-img .placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #ddd; font-size: 2rem; background: #f8f8f8; }
.related-card-body { padding: 14px; }
.related-card-body h4 { font-size: 12px; font-weight: 600; color: #002868; margin-bottom: 6px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.related-card-body .related-price { font-size: 14px; font-weight: 700; color: #002868; }

/* Responsive */
@media (max-width: 992px) {
    .product-grid { grid-template-columns: 1fr; gap: 30px; }
    .product-heading-top { display: block; text-align: left; width: 100%; }
    .product-info .product-heading-desktop { display: none; }
    .product-image-section {
        position: static;
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
    }
    .product-image-wrap {
        position: static;
        width: 100%;
        max-width: min(92vw, 520px);
        margin-inline: auto;
        height: auto;
        aspect-ratio: 1 / 1;
        min-height: 0;
    }
    .product-details-section { grid-template-columns: 1fr; }
    .product-sidebar { position: static; }
    .related-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .product-sidebar { display: none; }
    .product-features-row { grid-template-columns: repeat(2, 1fr); }
    .trust-badges { grid-template-columns: repeat(2, 1fr); }
    .trust-badge { border-bottom: 1px solid #eee; }
    .product-title { font-size: 1.3rem; }
    .tabs-header { overflow-x: auto; }
    .tab-btn { padding: 12px 16px; white-space: nowrap; font-size: 12px; }
    .author-section { flex-direction: column; text-align: center; align-items: center; }
    .author-left { flex-direction: column; align-items: center; }
    .author-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .author-stats-col { display: contents; }
    .author-stat-item { flex-direction: row; align-items: center; text-align: left; }

    /* Fix thumbnails overflow on mobile */
    .product-image-section {
        max-width: 100%;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .product-image-wrap {
        width: 100%;
        max-width: min(88vw, 480px);
        margin-inline: auto;
        height: auto;
        aspect-ratio: 1 / 1;
        min-height: 0;
        padding: 12px;
    }
    .product-image-wrap img {
        width: 100%;
        height: 100%;
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        object-position: center;
    }
    .product-thumbnails {
        width: 100%;
        max-width: min(88vw, 480px);
        margin-inline: auto;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .thumb { width: 52px; height: 52px; min-width: 52px; }

    /* Sticky Add to Cart / Buy Now above bottom nav */
    .product-sticky-actions {
        display: block;
        position: fixed;
        left: 0;
        right: 0;
        bottom: calc(12px + var(--lumak-mobile-nav-h, 76px) + env(safe-area-inset-bottom, 0px));
        z-index: 9979;
        padding: 10px 16px;
        background: rgba(255, 255, 255, 0.97);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-top: 1px solid #e8ecf5;
        box-shadow: 0 -4px 24px rgba(0, 40, 104, 0.1);
        transform: translateY(calc(100% + 24px));
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.28s ease, visibility 0.28s;
    }
    .product-sticky-actions.is-visible {
        transform: translateY(0);
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    .product-sticky-actions-inner {
        display: flex;
        gap: 10px;
        max-width: 600px;
        margin: 0 auto;
    }
    .product-sticky-actions-inner form {
        flex: 1;
        min-width: 0;
    }
    .product-sticky-actions .btn-add-cart,
    .product-sticky-actions .btn-buy-now {
        padding: 12px 14px;
        font-size: 13px;
        gap: 8px;
    }
    .product-sticky-actions .btn-add-cart i,
    .product-sticky-actions .btn-buy-now i {
        font-size: 14px;
    }
}
@media (max-width: 576px) {
    .product-actions { flex-direction: column; }
    .related-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
    .product-price { font-size: 1.5rem; }
    .quantity-section { flex-direction: column; align-items: flex-start; }

    /* Smaller thumbnails on very small screens */
    .product-image-wrap {
        max-width: min(86vw, 400px);
        padding: 10px;
    }
    .product-thumbnails { max-width: min(86vw, 400px); gap: 6px; }
    .thumb { width: 44px; height: 44px; min-width: 44px; }
}
</style>

<!-- Breadcrumb -->
<div class="product-page-breadcrumb">
    <div class="container">
        <a href="<?php echo BASE_URL; ?>">Home</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <a href="<?php echo BASE_URL; ?>shop.php">Shop</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <?php if ($product['category_name']): ?>
        <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo htmlspecialchars($product['category_slug']); ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>
        <span><?php echo htmlspecialchars($product['name']); ?></span>
    </div>
</div>

<!-- Product Section -->
<section class="product-section">
    <div class="container">
        <!-- Product name shown above image on mobile/tablet -->
        <div class="product-heading-top">
            <?php if ($product['category_name']): ?>
            <span class="product-category-badge"><?php echo htmlspecialchars($product['category_name']); ?></span>
            <?php endif; ?>
            <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="product-author">By <span><?php echo htmlspecialchars($product['author']); ?></span></p>
        </div>

        <!-- Product Main Grid -->
        <div class="product-grid">
            <!-- Product Image -->
            <div class="product-image-section">
                <div class="product-image-wrap">
                    <?php if ($product['image']): ?>
                    <img src="<?php echo htmlspecialchars(media_image_url($product['image'])); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="mainProductImg">
                    <?php else: ?>
                    <div class="product-image-placeholder">
                        <i class="fas fa-camera"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <?php 
                $galleryImages = [];
                if ($product['images']) {
                    $galleryImages = json_decode($product['images'], true) ?: [];
                }
                if ($product['image'] || !empty($galleryImages)): 
                ?>
                <div class="product-thumbnails">
                    <?php if ($product['image']): ?>
                    <div class="thumb active" onclick="switchImage('<?php echo htmlspecialchars(media_image_url($product['image']), ENT_QUOTES, 'UTF-8'); ?>', this)">
                        <img src="<?php echo htmlspecialchars(media_image_url($product['image'])); ?>" alt="">
                    </div>
                    <?php endif; ?>
                    <?php foreach ($galleryImages as $gImg): ?>
                    <div class="thumb" onclick="switchImage('<?php echo htmlspecialchars(media_image_url($gImg), ENT_QUOTES, 'UTF-8'); ?>', this)">
                        <img src="<?php echo htmlspecialchars(media_image_url($gImg)); ?>" alt="">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <div class="product-heading-desktop">
                    <?php if ($product['category_name']): ?>
                    <span class="product-category-badge"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    <?php endif; ?>
                    
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <p class="product-author">By <span><?php echo htmlspecialchars($product['author']); ?></span></p>
                </div>
                <?php if (!empty($product['warranty_period'])): ?>
                <p style="font-size:13px;color:#002868;margin:0 0 12px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-shield-alt"></i>
                    <strong><?php echo htmlspecialchars(ezymart_warranty_period_label($product['warranty_period'])); ?></strong> warranty included
                </p>
                <?php endif; ?>
                
                <div class="product-rating">
                    <span class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= floor($avgRating)): ?>
                                <i class="fas fa-star"></i>
                            <?php elseif ($i - $avgRating < 1 && $i - $avgRating > 0): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </span>
                    <span class="rating-text"><strong><?php echo $avgRating; ?></strong> (<?php echo $totalReviews; ?> review<?php echo $totalReviews !== 1 ? 's' : ''; ?>)</span>
                </div>

                <!-- Price -->
                <div class="product-price-section" id="productPriceSection">
                    <span class="product-price" id="productPriceDisplay"><?php echo format_price($product['price']); ?></span>
                    <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                    <span class="product-original-price" id="productOriginalPriceDisplay"><?php echo format_price($product['original_price']); ?></span>
                    <span class="product-discount-badge" id="productDiscountBadge"><?php echo $discountPercent; ?>% OFF</span>
                    <?php endif; ?>
                </div>

                <!-- Share Product -->
                <div class="product-share">
                    <span class="product-share-label"><i class="fas fa-share-alt"></i> Share:</span>
                    <div class="product-share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($productShareUrl); ?>"
                           target="_blank" rel="noopener noreferrer" class="share-btn share-fb" aria-label="Share on Facebook" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode($productShareMessage); ?>"
                           target="_blank" rel="noopener noreferrer" class="share-btn share-wa" aria-label="Share on WhatsApp" title="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($product['name'] . ' — ' . $productSharePrice); ?>&url=<?php echo urlencode($productShareUrl); ?>"
                           target="_blank" rel="noopener noreferrer" class="share-btn share-tw" aria-label="Share on X" title="X (Twitter)">
                            <i class="fab fa-x-twitter"></i>
                        </a>
                        <a href="https://t.me/share/url?url=<?php echo urlencode($productShareUrl); ?>&text=<?php echo urlencode($product['name'] . ' — ' . $productSharePrice); ?>"
                           target="_blank" rel="noopener noreferrer" class="share-btn share-tg" aria-label="Share on Telegram" title="Telegram">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                        <a href="https://pinterest.com/pin/create/button/?url=<?php echo urlencode($productShareUrl); ?>&media=<?php echo urlencode($productShareImage); ?>&description=<?php echo urlencode($product['name'] . ' — ' . $productSharePrice); ?>"
                           target="_blank" rel="noopener noreferrer" class="share-btn share-pin" aria-label="Share on Pinterest" title="Pinterest">
                            <i class="fab fa-pinterest-p"></i>
                        </a>
                        <button type="button" class="share-btn share-copy" id="productCopyLinkBtn" data-share-url="<?php echo htmlspecialchars($productShareUrl); ?>" title="Copy link">
                            <i class="fas fa-link"></i> Copy
                        </button>
                    </div>
                </div>

                <?php if ($hasVariations && !empty($variationData['options'])): ?>
                <div class="product-variations" id="productVariations"
                     data-base-price="<?php echo htmlspecialchars((string)$product['price']); ?>"
                     data-base-original="<?php echo htmlspecialchars((string)($product['original_price'] ?? '')); ?>">
                    <?php foreach ($variationData['options'] as $option): ?>
                    <div class="product-variation-group" data-option-name="<?php echo htmlspecialchars($option['name']); ?>">
                        <span class="product-variation-label"><?php echo htmlspecialchars($option['name']); ?></span>
                        <div class="product-variation-options">
                            <?php foreach ($option['values'] as $value):
                                $isColorOption = strtolower($option['name']) === 'color';
                                $displayValue = $value;
                                $swatchColor = '';
                                if ($isColorOption) {
                                    if (strpos($value, '|') !== false) {
                                        [$displayValue, $swatchColor] = array_map('trim', explode('|', $value, 2));
                                    } elseif (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
                                        $swatchColor = $value;
                                        $displayValue = strtoupper($value);
                                    }
                                }
                                $btnClass = 'product-variation-btn' . ($swatchColor ? ' has-color-swatch' : '');
                                $btnStyle = $swatchColor ? ' style="--swatch-color:' . htmlspecialchars($swatchColor) . ';"' : '';
                            ?>
                            <button type="button"
                                    class="<?php echo $btnClass; ?>"
                                    <?php echo $btnStyle; ?>
                                    data-option="<?php echo htmlspecialchars($option['name']); ?>"
                                    data-value="<?php echo htmlspecialchars($value); ?>">
                                <?php echo htmlspecialchars($displayValue); ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <p class="product-variation-selected" id="variationSelectedText">Selected: —</p>
                    <p class="product-variation-hint" id="variationHintText">Please select all options to continue.</p>
                </div>
                <?php endif; ?>

                <!-- Stock Status -->
                <div id="productStockStatus">
                <?php if ($canPurchase && !$hasVariations && $product['stock'] > 10): ?>
                <div class="stock-status in-stock">
                    <i class="fas fa-check-circle"></i>
                    <span>In Stock (<?php echo $product['stock']; ?> available)</span>
                </div>
                <?php elseif ($canPurchase && !$hasVariations && $product['stock'] > 0): ?>
                <div class="stock-status low-stock">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Low Stock - Only <?php echo $product['stock']; ?> left!</span>
                </div>
                <?php elseif ($canPurchase && $hasVariations): ?>
                <div class="stock-status in-stock" id="variationStockStatus">
                    <i class="fas fa-check-circle"></i>
                    <span>Select your options to check availability</span>
                </div>
                <?php else: ?>
                <div class="stock-status out-of-stock">
                    <i class="fas fa-times-circle"></i>
                    <span>Out of Stock</span>
                </div>
                <?php endif; ?>
                </div>

                <div class="restock-notify<?php echo $restockAlreadyWaiting ? ' is-waiting' : ''; ?>" id="restockNotifyBox" <?php echo $canPurchase ? 'hidden' : ''; ?>>
                    <div id="restockNotifyFormWrap" <?php echo $restockAlreadyWaiting ? 'hidden' : ''; ?>>
                        <h3 class="restock-notify__title"><i class="fas fa-bell"></i> Notify me when back in stock</h3>
                        <p class="restock-notify__desc" id="restockNotifyDesc">
                            <?php if ($hasVariations): ?>
                            Enter your details and we will email<?php echo ezymart_sms_is_configured() ? ' and SMS' : ''; ?> you when this option is available again.
                            <?php else: ?>
                            Enter your details and we will email<?php echo ezymart_sms_is_configured() ? ' and SMS' : ''; ?> you as soon as this item is restocked.
                            <?php endif; ?>
                        </p>
                        <form id="restockNotifyForm" class="restock-notify__form" novalidate>
                            <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                            <input type="hidden" name="variation_key" id="restockVariationKey" value="">
                            <div class="restock-notify__fields">
                                <div class="restock-notify__field">
                                    <label for="restockEmail">Email address</label>
                                    <input type="email" id="restockEmail" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($restockUserEmail); ?>" required>
                                </div>
                                <div class="restock-notify__field">
                                    <label for="restockPhone">Phone number <?php echo ezymart_sms_is_configured() ? '' : '(optional)'; ?></label>
                                    <input type="tel" id="restockPhone" name="phone" placeholder="07X XXX XXXX" value="<?php echo htmlspecialchars($restockUserPhone); ?>" <?php echo ezymart_sms_is_configured() ? 'required' : ''; ?>>
                                </div>
                            </div>
                            <button type="submit" class="restock-notify__submit" id="restockNotifyBtn">
                                <i class="fas fa-bell"></i> Notify Me
                            </button>
                            <div class="restock-notify__message" id="restockNotifyMessage" hidden></div>
                        </form>
                    </div>

                    <div class="restock-notify__waiting" id="restockNotifyWaiting" <?php echo $restockAlreadyWaiting ? '' : 'hidden'; ?>>
                        <div class="restock-notify__waiting-icon" aria-hidden="true"><i class="fas fa-hourglass-half restock-notify__hourglass"></i></div>
                        <h3 class="restock-notify__waiting-title">You Are on the Restock Waiting List</h3>
                        <p class="restock-notify__waiting-text">
                            Thank you for your interest. You are currently waiting for this item to be restocked.
                            We will notify you<?php echo ezymart_sms_is_configured() ? ' by email and SMS' : ' by email'; ?> as soon as it becomes available again.
                        </p>
                        <div class="restock-notify__waiting-details">
                            <div class="restock-notify__waiting-row">
                                <span>Email</span>
                                <strong id="restockWaitingEmail"><?php echo htmlspecialchars($restockUserEmail); ?></strong>
                            </div>
                            <div class="restock-notify__waiting-row" id="restockWaitingPhoneRow" <?php echo $restockUserPhone === '' ? 'hidden' : ''; ?>>
                                <span>Phone</span>
                                <strong id="restockWaitingPhone"><?php echo htmlspecialchars($restockUserPhone); ?></strong>
                            </div>
                        </div>
                        <p class="restock-notify__waiting-note">
                            <i class="fas fa-check-circle"></i> No further action is required. Please wait until we confirm the restock.
                        </p>
                    </div>
                </div>

                <!-- Feature Icons Row -->
                <div class="product-features-row">
                    <div class="feature-item">
                        <i class="fas fa-bullseye"></i>
                        <span>Trending Styles</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-double"></i>
                        <span>Model Answers</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-tags"></i>
                        <span>Latest Trends</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Premium Quality</span>
                    </div>
                </div>

                <!-- Quantity Selector -->
                <?php if ($canPurchase): ?>
                <div class="quantity-section" id="productQuantitySection" <?php echo $hasVariations ? 'style="display:none;"' : ''; ?>>
                    <span class="quantity-label">Quantity:</span>
                    <div class="quantity-selector">
                        <button type="button" class="qty-btn" onclick="changeQty(-1)">-</button>
                        <input type="number" class="qty-input" id="quantity" value="1" min="1" max="<?php echo (int)$product['stock']; ?>" readonly>
                        <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="product-actions" id="productActions">
                    <form action="<?php echo BASE_URL; ?>controllers/CartController.php" method="POST" style="flex:1;" class="js-product-cart-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="variation_id" value="0" class="js-variation-id-input">
                        <input type="hidden" name="quantity" value="1" id="cartQtyInput">
                        <input type="hidden" name="redirect_url" value="<?php echo BASE_URL; ?>product.php?slug=<?php echo htmlspecialchars($product['slug']); ?>">
                        <button type="submit" class="btn-add-cart js-product-submit" style="width:100%;" <?php echo $hasVariations ? 'disabled' : ''; ?>>
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </form>
                    <form action="<?php echo BASE_URL; ?>controllers/CartController.php" method="POST" style="flex:1;" class="js-product-cart-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="variation_id" value="0" class="js-variation-id-input">
                        <input type="hidden" name="quantity" value="1" id="buyQtyInput">
                        <input type="hidden" name="redirect_url" value="<?php echo BASE_URL; ?>cart.php">
                        <button type="submit" class="btn-buy-now js-product-submit" style="width:100%;" <?php echo $hasVariations ? 'disabled' : ''; ?>>
                            <i class="fas fa-bolt"></i> Buy Now
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Wishlist Button -->
                <?php
                $isInWishlist = false;
                if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && empty($_SESSION['is_admin'])) {
                    try {
                        if (!class_exists('WishlistDAL')) { require_once __DIR__ . '/../dal/WishlistDAL.php'; }
                        $wlCheck = new WishlistDAL();
                        $isInWishlist = $wlCheck->isInWishlist($_SESSION['user_id'], $product['id']);
                    } catch (Exception $e) {}
                }
                ?>
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && empty($_SESSION['is_admin'])): ?>
                <form action="<?php echo BASE_URL; ?>wishlist-api.php" method="POST" class="product-wishlist-form ez-product-card__wish-form" data-ajax data-wishlist-toggle>
                    <input type="hidden" name="action" value="<?php echo $isInWishlist ? 'remove' : 'add'; ?>">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="redirect_url" value="<?php echo BASE_URL; ?>product.php?slug=<?php echo htmlspecialchars($product['slug']); ?>">
                    <button type="submit" class="product-wishlist-btn<?php echo $isInWishlist ? ' is-active' : ''; ?>" aria-label="<?php echo $isInWishlist ? 'Remove from wishlist' : 'Add to wishlist'; ?>">
                        <i class="<?php echo $isInWishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                        <span class="product-wishlist-btn__label"><?php echo $isInWishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?></span>
                    </button>
                </form>
                <?php else: ?>
                <a href="<?php echo BASE_URL; ?>login.php" class="product-wishlist-btn" style="text-decoration:none; margin-bottom:20px;">
                    <i class="far fa-heart"></i>
                    <span class="product-wishlist-btn__label">Add to Wishlist</span>
                </a>
                <?php endif; ?>

                <!-- Trust Badges -->
                <div class="trust-badges">
                    <div class="trust-badge">
                        <i class="fas fa-truck"></i>
                        <span>Free Delivery</span>
                    </div>
                    <div class="trust-badge">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure Payments</span>
                    </div>
                    <div class="trust-badge">
                        <i class="fas fa-undo"></i>
                        <span>Easy Returns</span>
                    </div>
                    <div class="trust-badge">
                        <i class="fas fa-headset"></i>
                        <span>Customer Support</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Details Section with Sidebar -->
        <div class="product-details-section">
            <!-- Sidebar -->
            <aside class="product-sidebar">
                <div class="sidebar-widget">
                    <h3>Product Categories</h3>
                    <ul class="category-list">
                        <?php foreach (array_slice($categories, 0, 9) as $cat): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>" class="<?php echo ($cat['id'] == $product['category_id']) ? 'active' : ''; ?>">
                                <span><?php echo htmlspecialchars($cat['name']); ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>

            <!-- Main Content with Tabs -->
            <div class="product-tabs">
                <div class="tabs-header">
                    <button class="tab-btn active" onclick="switchTab('description')">Description</button>
                    <button class="tab-btn" onclick="switchTab('details')">Product Details</button>
                    <button class="tab-btn" onclick="switchTab('reviews')">Reviews</button>
                </div>

                <div class="tab-content active" id="tab-description">
                    <?php if ($product['description']): ?>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    <?php else: ?>
                    <p>No description available for this product.</p>
                    <?php endif; ?>
                </div>

                <div class="tab-content" id="tab-details">
                    <?php if ($product['details']): ?>
                    <p><?php echo nl2br(htmlspecialchars($product['details'])); ?></p>
                    <?php else: ?>
                    <p>No additional details available.</p>
                    <?php endif; ?>
                </div>

                <div class="tab-content" id="tab-reviews">

                    <!-- Review Summary -->
                    <div class="review-summary">
                        <div class="review-avg">
                            <span class="avg-number"><?php echo $avgRating ?: '0'; ?></span>
                            <div>
                                <div class="stars" style="color:#c9a96e; font-size:14px;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= floor($avgRating)): ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif ($i - $avgRating < 1): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <span class="review-count"><?php echo $totalReviews; ?> review(s)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Reviews -->
                    <div class="reviews-list">
                        <?php if (empty($reviews)): ?>
                        <p class="no-reviews">No reviews yet. Be the first to review this product!</p>
                        <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-avatar">
                                    <?php echo strtoupper(substr($review['user_name'], 0, 1)); ?>
                                </div>
                                <div class="reviewer-info">
                                    <strong><?php echo htmlspecialchars($review['user_name']); ?></strong>
                                    <div class="review-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star" style="color:<?php echo $i <= $review['rating'] ? '#c9a96e' : '#ddd'; ?>; font-size:12px;"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Add Review Form (logged in users only) -->
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true)): ?>
                    <div class="add-review-form">
                        <h4>Write a Review</h4>
                        <form action="<?php echo BASE_URL; ?>controllers/ReviewController.php" method="POST">
                            <input type="hidden" name="action" value="add_review">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="product_slug" value="<?php echo htmlspecialchars($product['slug']); ?>">
                            
                            <div class="rating-input">
                                <label>Your Rating</label>
                                <div class="star-rating-input">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" <?php echo $i === 5 ? 'checked' : ''; ?>>
                                    <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="review-textarea">
                                <label>Your Review</label>
                                <textarea name="comment" rows="4" placeholder="Share your experience with this product..." required></textarea>
                            </div>

                            <button type="submit" class="btn-submit-review">
                                <i class="fas fa-paper-plane"></i> Submit Review
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="login-to-review">
                        <p><i class="fas fa-lock"></i> <a href="<?php echo BASE_URL; ?>login.php">Login</a> to write a review.</p>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Author Section (below tabs) -->
        <div class="author-section">
            <div class="author-left">
                <div class="author-details">
                    <span class="author-label">About the Brand</span>
                    <h4><?php echo SITE_NAME; ?></h4>
                    <p class="author-role"><?php echo STORE_TAGLINE; ?></p>
                    <p class="author-exp">Direct source supplier of high-quality Sri Lankan sapphires, fine jewelry and certified precious gemstones.<br>Operated by <?php echo STORE_OWNER; ?> from <?php echo STORE_LOCATION; ?>. Shop online at <?php echo STORE_DOMAIN; ?>.</p>
                    <a href="<?php echo BASE_URL; ?>about.php" class="author-link">View More About Us <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="author-stats">
                <div class="author-stats-col">
                    <div class="author-stat-item">
                        <div class="author-stat-icon"><i class="fas fa-certificate"></i></div>
                        <div>
                            <strong>100%</strong>
                            <span>Certified Authentic</span>
                        </div>
                    </div>
                    <div class="author-stat-item">
                        <div class="author-stat-icon"><i class="fas fa-gem"></i></div>
                        <div>
                            <strong>Direct</strong>
                            <span>Mine Source</span>
                        </div>
                    </div>
                    <div class="author-stat-item">
                        <div class="author-stat-icon"><i class="fas fa-headset"></i></div>
                        <div>
                            <strong>24/7</strong>
                            <span>Direct Support</span>
                        </div>
                    </div>
                </div>
                <div class="author-stats-col">
                    <div class="author-stat-item">
                        <div class="author-stat-icon"><i class="fas fa-globe-asia"></i></div>
                        <div>
                            <strong>Global</strong>
                            <span>Worldwide Reach</span>
                        </div>
                    </div>
                    <div class="author-stat-item">
                        <div class="author-stat-icon"><i class="fas fa-shield-alt"></i></div>
                        <div>
                            <strong>Ethical</strong>
                            <span>State Gem Corp Registered</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
        <div class="related-section">
            <div class="related-header">
                <h2>You May Also Like</h2>
                <?php if (count($relatedProducts) > 4): ?>
                <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo htmlspecialchars($product['category_slug']); ?>" class="related-view-all">View All <i class="fas fa-arrow-right"></i></a>
                <?php endif; ?>
            </div>
            <div class="related-grid">
                <?php foreach (array_slice($relatedProducts, 0, 4) as $related): ?>
                <a href="<?php echo BASE_URL; ?>product.php?slug=<?php echo htmlspecialchars($related['slug']); ?>" class="related-card">
                    <div class="related-card-img">
                        <?php if ($related['image']): ?>
                        <img src="<?php echo htmlspecialchars(media_image_url($related['image'])); ?>" alt="<?php echo htmlspecialchars($related['name']); ?>">
                        <?php else: ?>
                        <div class="placeholder"><i class="fas fa-camera"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="related-card-body">
                        <h4><?php echo htmlspecialchars($related['name']); ?></h4>
                        <span class="related-price"><?php echo format_price($related['price']); ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($canPurchase): ?>
<div class="product-sticky-actions" id="productStickyActions" aria-hidden="true">
    <div class="product-sticky-actions-inner">
        <form action="<?php echo BASE_URL; ?>controllers/CartController.php" method="POST" class="js-product-cart-form">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="variation_id" value="0" class="js-variation-id-input">
            <input type="hidden" name="quantity" value="1" id="cartQtyInputSticky">
            <input type="hidden" name="redirect_url" value="<?php echo BASE_URL; ?>product.php?slug=<?php echo htmlspecialchars($product['slug']); ?>">
            <button type="submit" class="btn-add-cart js-product-submit" style="width:100%;" <?php echo $hasVariations ? 'disabled' : ''; ?>>
                <i class="fas fa-shopping-cart"></i> Add to Cart
            </button>
        </form>
        <form action="<?php echo BASE_URL; ?>controllers/CartController.php" method="POST" class="js-product-cart-form">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="variation_id" value="0" class="js-variation-id-input">
            <input type="hidden" name="quantity" value="1" id="buyQtyInputSticky">
            <input type="hidden" name="redirect_url" value="<?php echo BASE_URL; ?>cart.php">
            <button type="submit" class="btn-buy-now js-product-submit" style="width:100%;" <?php echo $hasVariations ? 'disabled' : ''; ?>>
                <i class="fas fa-bolt"></i> Buy Now
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
var PRODUCT_VARIATIONS = <?php echo json_encode($variationData['variations'] ?? [], JSON_UNESCAPED_UNICODE); ?>;
var PRODUCT_HAS_VARIATIONS = <?php echo $hasVariations ? 'true' : 'false'; ?>;
var PRODUCT_CAN_PURCHASE = <?php echo $canPurchase ? 'true' : 'false'; ?>;
var RESTOCK_USER_LOGGED_IN = <?php echo $restockUserLoggedIn ? 'true' : 'false'; ?>;
var PRODUCT_BASE_PRICE = <?php echo json_encode((float)$product['price']); ?>;
var PRODUCT_BASE_ORIGINAL = <?php echo json_encode($product['original_price'] ? (float)$product['original_price'] : null); ?>;
var selectedVariationOptions = {};
var activeVariation = null;
var currentVariationStock = <?php echo (int)$product['stock']; ?>;

function parseColorValue(value) {
    if (!value) return { label: '', hex: '' };
    if (value.indexOf('|') !== -1) {
        var parts = value.split('|');
        return { label: parts[0].trim(), hex: parts[1].trim() };
    }
    if (/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(value)) {
        return { label: value.toUpperCase(), hex: value };
    }
    return { label: value, hex: '' };
}

function formatOptionLabel(optionName, value) {
    if (String(optionName).toLowerCase() === 'color') {
        return parseColorValue(value).label || value;
    }
    return value;
}

function formatMoney(amount) {
    return 'LKR ' + Number(amount).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function optionsMatch(selected, variationOptions) {
    var keys = Object.keys(variationOptions || {});
    return keys.every(function(key) { return selected[key] === variationOptions[key]; });
}

function findMatchingVariation() {
    var groups = document.querySelectorAll('.product-variation-group');
    if (!groups.length) return null;
    var required = groups.length;
    var selectedCount = Object.keys(selectedVariationOptions).length;
    if (selectedCount < required) return null;
    for (var i = 0; i < PRODUCT_VARIATIONS.length; i++) {
        if (optionsMatch(selectedVariationOptions, PRODUCT_VARIATIONS[i].options)) {
            return PRODUCT_VARIATIONS[i];
        }
    }
    return null;
}

function updateVariationUI() {
    var selectedText = document.getElementById('variationSelectedText');
    var hintText = document.getElementById('variationHintText');
    var stockStatus = document.getElementById('variationStockStatus');
    var priceDisplay = document.getElementById('productPriceDisplay');
    var qtySection = document.getElementById('productQuantitySection');
    var qtyInput = document.getElementById('quantity');
    var submitButtons = document.querySelectorAll('.js-product-submit');
    var variationInputs = document.querySelectorAll('.js-variation-id-input');

    activeVariation = findMatchingVariation();
    var label = Object.keys(selectedVariationOptions).map(function(key) {
        return key + ': ' + formatOptionLabel(key, selectedVariationOptions[key]);
    }).join(' / ');

    if (selectedText) {
        selectedText.textContent = label ? ('Selected: ' + label) : 'Selected: —';
    }

    if (!activeVariation) {
        if (hintText) hintText.textContent = 'Please select all options to continue.';
        if (stockStatus) {
            stockStatus.className = 'stock-status in-stock';
            stockStatus.innerHTML = '<i class="fas fa-check-circle"></i><span>Select your options to check availability</span>';
        }
        if (priceDisplay) priceDisplay.textContent = formatMoney(PRODUCT_BASE_PRICE);
        if (qtySection) qtySection.style.display = 'none';
        submitButtons.forEach(function(btn) { btn.disabled = true; });
        variationInputs.forEach(function(input) { input.value = '0'; });
        updateRestockNotifyUI(false, '');
        return;
    }

    var price = activeVariation.price !== null && activeVariation.price !== undefined ? activeVariation.price : PRODUCT_BASE_PRICE;
    currentVariationStock = parseInt(activeVariation.stock, 10) || 0;
    if (priceDisplay) priceDisplay.textContent = formatMoney(price);
    variationInputs.forEach(function(input) { input.value = String(activeVariation.id); });

    if (currentVariationStock > 0) {
        if (hintText) hintText.textContent = activeVariation.label || label;
        if (stockStatus) {
            stockStatus.className = currentVariationStock > 10 ? 'stock-status in-stock' : 'stock-status low-stock';
            stockStatus.innerHTML = currentVariationStock > 10
                ? '<i class="fas fa-check-circle"></i><span>In Stock (' + currentVariationStock + ' available)</span>'
                : '<i class="fas fa-exclamation-circle"></i><span>Low Stock - Only ' + currentVariationStock + ' left!</span>';
        }
        if (qtySection) qtySection.style.display = '';
        if (qtyInput) {
            qtyInput.max = currentVariationStock;
            if (parseInt(qtyInput.value, 10) > currentVariationStock) qtyInput.value = currentVariationStock;
        }
        submitButtons.forEach(function(btn) { btn.disabled = false; });
        updateRestockNotifyUI(false, '');
    } else {
        if (hintText) hintText.textContent = 'This combination is currently out of stock.';
        if (stockStatus) {
            stockStatus.className = 'stock-status out-of-stock';
            stockStatus.innerHTML = '<i class="fas fa-times-circle"></i><span>Out of Stock</span>';
        }
        if (qtySection) qtySection.style.display = 'none';
        submitButtons.forEach(function(btn) { btn.disabled = true; });
        updateRestockNotifyUI(true, activeVariation.label || label || '');
    }
}

function updateRestockNotifyUI(show, variationKey) {
    var box = document.getElementById('restockNotifyBox');
    var keyInput = document.getElementById('restockVariationKey');
    if (!box) return;
    if (!PRODUCT_HAS_VARIATIONS) return;
    box.hidden = !show;
    if (keyInput) keyInput.value = variationKey || '';
    if (show) {
        checkRestockWaitingState(variationKey || '');
    }
}

(function initProductVariations() {
    if (!PRODUCT_HAS_VARIATIONS) return;
    document.querySelectorAll('.product-variation-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var option = this.getAttribute('data-option');
            var value = this.getAttribute('data-value');
            selectedVariationOptions[option] = value;
            document.querySelectorAll('.product-variation-btn[data-option="' + option + '"]').forEach(function(other) {
                other.classList.toggle('is-active', other === btn);
            });
            updateVariationUI();
        });
    });
    updateVariationUI();
})();

// Switch product image from thumbnails
function switchImage(src, thumb) {
    var mainImg = document.getElementById('mainProductImg');
    if (mainImg) {
        mainImg.src = src;
        // Reset zoom on image switch
        mainImg.closest('.product-image-wrap').classList.remove('zoomed');
        mainImg.style.transformOrigin = 'center center';
    }
    // Update active state
    document.querySelectorAll('.thumb').forEach(function(t) { t.classList.remove('active'); });
    thumb.classList.add('active');
}

// Image zoom on click and mouse move
(function() {
    var wrap = document.querySelector('.product-image-wrap');
    var img = document.getElementById('mainProductImg');
    if (!wrap || !img) return;

    wrap.addEventListener('click', function() {
        this.classList.toggle('zoomed');
    });

    wrap.addEventListener('mousemove', function(e) {
        if (!this.classList.contains('zoomed')) return;
        var rect = this.getBoundingClientRect();
        var x = ((e.clientX - rect.left) / rect.width) * 100;
        var y = ((e.clientY - rect.top) / rect.height) * 100;
        img.style.transformOrigin = x + '% ' + y + '%';
    });

    wrap.addEventListener('mouseleave', function() {
        this.classList.remove('zoomed');
        img.style.transformOrigin = 'center center';
    });
})();

// Quantity selector
function changeQty(delta) {
    var input = document.getElementById('quantity');
    if (!input) return;
    var current = parseInt(input.value, 10);
    var max = parseInt(input.max, 10) || currentVariationStock || 1;
    var newVal = current + delta;
    if (newVal >= 1 && newVal <= max) {
        input.value = newVal;
        // Sync with cart form inputs
        ['cartQtyInput', 'buyQtyInput', 'cartQtyInputSticky', 'buyQtyInputSticky'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.value = newVal;
        });
    }
}

// Tab switching
function switchTab(tab) {
    // Remove active from all tabs and contents
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.tab-content').forEach(function(content) {
        content.classList.remove('active');
    });
    
    // Activate clicked tab
    event.target.classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
}

// Cart functions (placeholder)
function addToCart(productId) {
    var qty = document.getElementById('quantity').value;
    alert('Added ' + qty + ' item(s) to cart! (Cart functionality coming soon)');
}

function buyNow(productId) {
    var qty = document.getElementById('quantity').value;
    alert('Proceeding to checkout with ' + qty + ' item(s)! (Checkout coming soon)');
}

// Auto-open reviews tab if redirected after review submission
window.addEventListener('DOMContentLoaded', function() {
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('review_success') || urlParams.has('review_error') || window.location.hash === '#tab-reviews') {
        // Switch to reviews tab
        document.querySelectorAll('.tab-btn').forEach(function(btn) { btn.classList.remove('active'); });
        document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('active'); });
        document.getElementById('tab-reviews').classList.add('active');
        var btns = document.querySelectorAll('.tab-btn');
        btns[btns.length - 1].classList.add('active');
        document.querySelector('.product-tabs').scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Show toast notification (only once)
        if (urlParams.has('review_success')) {
            showToast('Thank you! Your review was submitted and will appear after admin approval.', 'success');
        } else if (urlParams.has('review_error')) {
            showToast('Failed to submit review. Please fill all fields.', 'error');
        }

        // Clean URL so it doesn't show again
        var cleanUrl = window.location.pathname + '?slug=' + urlParams.get('slug');
        window.history.replaceState({}, '', cleanUrl + '#tab-reviews');
    }
});

function showToast(message, type) {
    var toast = document.createElement('div');
    toast.className = 'alert alert-' + type;
    toast.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;
    document.body.appendChild(toast);
}

(function initRestockNotifyForm() {
    var productId = <?php echo (int)$product['id']; ?>;
    var form = document.getElementById('restockNotifyForm');
    var formWrap = document.getElementById('restockNotifyFormWrap');
    var waitingPanel = document.getElementById('restockNotifyWaiting');
    var box = document.getElementById('restockNotifyBox');

    window.showRestockWaitingState = function(email, phone) {
        var waitingEmail = document.getElementById('restockWaitingEmail');
        var waitingPhone = document.getElementById('restockWaitingPhone');
        var waitingPhoneRow = document.getElementById('restockWaitingPhoneRow');

        if (formWrap) formWrap.hidden = true;
        if (waitingPanel) waitingPanel.hidden = false;
        if (box) box.classList.add('is-waiting');
        if (waitingEmail) waitingEmail.textContent = email || '';
        if (waitingPhone) waitingPhone.textContent = phone || '';
        if (waitingPhoneRow) waitingPhoneRow.hidden = !phone;
    };

    window.showRestockSubscribeForm = function() {
        if (formWrap) formWrap.hidden = false;
        if (waitingPanel) waitingPanel.hidden = true;
        if (box) box.classList.remove('is-waiting');
    };

    window.checkRestockWaitingState = function(variationKey) {
        if (!RESTOCK_USER_LOGGED_IN) {
            showRestockSubscribeForm();
            return;
        }

        var emailInput = document.getElementById('restockEmail');
        var email = emailInput ? emailInput.value.trim() : '';
        if (!email) {
            showRestockSubscribeForm();
            return;
        }

        var body = new URLSearchParams();
        body.append('action', 'status');
        body.append('ajax', '1');
        body.append('product_id', String(productId));
        body.append('email', email);
        body.append('variation_key', variationKey || '');

        fetch('<?php echo BASE_URL; ?>restock-notify-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.subscribed) {
                var phoneInput = document.getElementById('restockPhone');
                showRestockWaitingState(email, data.phone || (phoneInput ? phoneInput.value.trim() : ''));
            } else {
                showRestockSubscribeForm();
            }
        })
        .catch(function() {
            showRestockSubscribeForm();
        });
    };

    if (RESTOCK_USER_LOGGED_IN && (!PRODUCT_HAS_VARIATIONS || !PRODUCT_CAN_PURCHASE)) {
        checkRestockWaitingState('');
    }

    if (!form || form.dataset.bound) return;
    form.dataset.bound = '1';

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (RESTOCK_USER_LOGGED_IN && waitingPanel && !waitingPanel.hidden) {
            return;
        }

        var btn = document.getElementById('restockNotifyBtn');
        var messageEl = document.getElementById('restockNotifyMessage');
        var email = (document.getElementById('restockEmail') || {}).value || '';
        var phone = (document.getElementById('restockPhone') || {}).value || '';
        var variationKey = (document.getElementById('restockVariationKey') || {}).value || '';

        if (!email.trim()) {
            if (messageEl) {
                messageEl.hidden = false;
                messageEl.className = 'restock-notify__message is-error';
                messageEl.textContent = 'Please enter your email address.';
            }
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        }
        if (messageEl) messageEl.hidden = true;

        var body = new URLSearchParams();
        body.append('action', 'subscribe');
        body.append('ajax', '1');
        body.append('product_id', String(productId));
        body.append('email', email.trim());
        body.append('phone', phone.trim());
        body.append('variation_key', variationKey.trim());

        fetch('<?php echo BASE_URL; ?>restock-notify-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showRestockWaitingState(email.trim(), phone.trim());
                if (!RESTOCK_USER_LOGGED_IN && btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-bell"></i> Notify Me';
                }
                return;
            }

            if (messageEl) {
                messageEl.hidden = false;
                messageEl.className = 'restock-notify__message is-error';
                messageEl.textContent = data.message || 'Could not save your alert.';
            }
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-bell"></i> Notify Me';
            }
        })
        .catch(function() {
            if (messageEl) {
                messageEl.hidden = false;
                messageEl.className = 'restock-notify__message is-error';
                messageEl.textContent = 'Could not save your alert. Please try again.';
            }
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-bell"></i> Notify Me';
            }
        });
    });

    if (!RESTOCK_USER_LOGGED_IN) {
        var backToFormBtn = document.createElement('button');
        backToFormBtn.type = 'button';
        backToFormBtn.className = 'restock-notify__back-btn';
        backToFormBtn.innerHTML = '<i class="fas fa-edit"></i> Submit another alert';
        backToFormBtn.addEventListener('click', function() {
            showRestockSubscribeForm();
        });
        if (waitingPanel) waitingPanel.appendChild(backToFormBtn);
    }
})();

function initProductShare() {
    var copyBtn = document.getElementById('productCopyLinkBtn');
    if (!copyBtn || copyBtn.dataset.bound) return;
    copyBtn.dataset.bound = '1';
    copyBtn.addEventListener('click', function() {
        var url = copyBtn.getAttribute('data-share-url') || window.location.href;
        var onCopied = function() {
            copyBtn.classList.add('is-copied');
            copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied';
            showToast('Product link copied!', 'success');
            setTimeout(function() {
                copyBtn.classList.remove('is-copied');
                copyBtn.innerHTML = '<i class="fas fa-link"></i> Copy';
            }, 2000);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(onCopied).catch(function() {
                window.prompt('Copy this link:', url);
            });
        } else {
            window.prompt('Copy this link:', url);
        }
    });
}

function initProductStickyActions() {
    window._lumakUpdateProductSticky = updateProductStickyVisibility;
    updateProductStickyVisibility();

    if (!window._lumakProductStickyBound) {
        window._lumakProductStickyBound = true;
        window.addEventListener('scroll', updateProductStickyVisibility, { passive: true });
        window.addEventListener('resize', updateProductStickyVisibility);
    }
}

function updateProductStickyVisibility() {
    var sentinel = document.getElementById('productActions');
    var sticky = document.getElementById('productStickyActions');
    if (!sentinel || !sticky) {
        document.body.classList.remove('has-product-sticky-actions');
        return;
    }

    if (!window.matchMedia('(max-width: 768px)').matches) {
        sticky.classList.remove('is-visible');
        sticky.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('has-product-sticky-actions');
        return;
    }

    // Only show after user scrolls past the in-page buttons (not when they are below the fold on load)
    var show = sentinel.getBoundingClientRect().bottom <= 0;
    sticky.classList.toggle('is-visible', show);
    sticky.setAttribute('aria-hidden', show ? 'false' : 'true');
    document.body.classList.toggle('has-product-sticky-actions', show);
}

document.addEventListener('turbo:load', initProductStickyActions);
document.addEventListener('turbo:load', initProductShare);
document.addEventListener('turbo:before-visit', function() {
    document.body.classList.remove('has-product-sticky-actions');
});
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initProductStickyActions();
        initProductShare();
    });
} else {
    initProductStickyActions();
    initProductShare();
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
