<?php
/**
 * Modern product card — expects $prod and $cardContext (from ezymart_build_product_card_context).
 * Optional: $cardBadge ('hot-deal'|'bestseller'|null), $cardShowCategory (bool).
 */
if (empty($prod) || !is_array($prod)) {
    return;
}

if (!function_exists('ezymart_product_card_metrics')) {
    require_once __DIR__ . '/../../helpers/product-card.php';
}

$cardContext = $cardContext ?? ezymart_build_product_card_context([$prod]);
$cardBadge = $cardBadge ?? null;
$cardShowCategory = $cardShowCategory ?? true;
$m = ezymart_product_card_metrics($prod, $cardContext);
$productUrl = BASE_URL . 'product.php?slug=' . rawurlencode($prod['slug'] ?? '');
$imageUrl = !empty($prod['image']) ? media_image_url($prod['image']) : '';
?>
<article class="ez-product-card"
    data-product-id="<?php echo $m['productId']; ?>"
    data-name="<?php echo htmlspecialchars($prod['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
    data-url="<?php echo htmlspecialchars($productUrl, ENT_QUOTES, 'UTF-8'); ?>"
    data-image="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>"
    data-price="<?php echo htmlspecialchars(format_price($m['price']), ENT_QUOTES, 'UTF-8'); ?>"
    data-original-price="<?php echo $m['hasDiscount'] ? htmlspecialchars(format_price($m['originalPrice']), ENT_QUOTES, 'UTF-8') : ''; ?>"
    data-discount="<?php echo $m['discountPct']; ?>"
    data-save="<?php echo $m['hasDiscount'] ? htmlspecialchars(format_price($m['saveAmount']), ENT_QUOTES, 'UTF-8') : ''; ?>"
    data-rating="<?php echo $m['avgRating']; ?>"
    data-reviews="<?php echo $m['reviewCount']; ?>"
    data-sold="<?php echo $m['soldCount']; ?>"
    data-stock="<?php echo $m['stock']; ?>"
    data-free-shipping="<?php echo $m['freeShipping'] ? '1' : '0'; ?>"
    data-has-variations="<?php echo $m['hasVariations'] ? '1' : '0'; ?>"
    data-category="<?php echo htmlspecialchars($prod['category_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <div class="ez-product-card__media">
        <a href="<?php echo htmlspecialchars($productUrl); ?>" class="ez-product-card__image-link" aria-label="<?php echo htmlspecialchars($prod['name'] ?? ''); ?>">
            <?php if ($imageUrl): ?>
            <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($prod['name'] ?? ''); ?>" class="ez-product-card__image" loading="lazy">
            <?php else: ?>
            <span class="ez-product-card__placeholder" aria-hidden="true"><i class="fas fa-pump-soap"></i></span>
            <?php endif; ?>
        </a>

        <div class="ez-product-card__media-badges">
            <?php if ($cardBadge === 'hot-deal'): ?>
            <span class="ez-product-card__badge ez-product-card__badge--hot">Hot Deal</span>
            <?php elseif ($cardBadge === 'bestseller' || !empty($prod['featured'])): ?>
            <span class="ez-product-card__badge ez-product-card__badge--bestseller">Bestseller</span>
            <?php elseif ($m['discountPct'] >= 5): ?>
            <span class="ez-product-card__badge ez-product-card__badge--discount"><?php echo $m['discountPct']; ?>% OFF</span>
            <?php endif; ?>
            <?php if ($m['freeShipping']): ?>
            <span class="ez-product-card__badge ez-product-card__badge--shipping"><i class="fas fa-shipping-fast"></i> Free Shipping</span>
            <?php endif; ?>
        </div>

        <div class="ez-product-card__media-actions">
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && empty($_SESSION['is_admin'])): ?>
            <form action="<?php echo BASE_URL; ?>wishlist-api.php" method="POST" class="ez-product-card__wish-form" data-ajax data-wishlist-toggle>
                <input type="hidden" name="action" value="<?php echo $m['inWishlist'] ? 'remove' : 'add'; ?>">
                <input type="hidden" name="product_id" value="<?php echo $m['productId']; ?>">
                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($m['redirectUrl']); ?>">
                <button type="submit" class="ez-product-card__icon-btn ez-product-card__wish<?php echo $m['inWishlist'] ? ' is-active' : ''; ?>" aria-label="<?php echo $m['inWishlist'] ? 'Remove from wishlist' : 'Add to wishlist'; ?>">
                    <i class="<?php echo $m['inWishlist'] ? 'fas' : 'far'; ?> fa-heart"></i>
                </button>
            </form>
            <?php else: ?>
            <a href="<?php echo BASE_URL; ?>login.php" class="ez-product-card__icon-btn ez-product-card__wish" aria-label="Login to save to wishlist">
                <i class="far fa-heart"></i>
            </a>
            <?php endif; ?>
            <button type="button" class="ez-product-card__icon-btn ez-product-card__quick-view" aria-label="Quick view" onclick="ezymartOpenQuickView(this.closest('.ez-product-card'))">
                <i class="fas fa-eye"></i>
            </button>
        </div>

        <div class="ez-product-card__hover-overlay" aria-hidden="true">
            <button type="button" class="ez-product-card__quick-btn" onclick="ezymartOpenQuickView(this.closest('.ez-product-card'))">
                <i class="fas fa-eye"></i>
                <span>Quick View</span>
            </button>
        </div>

        <div class="ez-product-card__processing" hidden aria-hidden="true">
            <span class="ez-product-card__processing-inner">
                <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                <span>Processing...</span>
            </span>
        </div>
    </div>

    <div class="ez-product-card__body">
        <?php if ($cardShowCategory && !empty($prod['category_name'])): ?>
        <span class="ez-product-card__category"><?php echo htmlspecialchars($prod['category_name']); ?></span>
        <?php endif; ?>

        <h3 class="ez-product-card__title">
            <a href="<?php echo htmlspecialchars($productUrl); ?>"><?php echo htmlspecialchars($prod['name'] ?? ''); ?></a>
        </h3>

        <div class="ez-product-card__rating">
            <span class="ez-product-card__rating-star" aria-hidden="true">★</span>
            <span class="ez-product-card__rating-value"><?php echo $m['avgRating'] > 0 ? number_format($m['avgRating'], 1) : '0.0'; ?></span>
            <span class="ez-product-card__rating-count">(<?php echo number_format($m['reviewCount']); ?> Review<?php echo $m['reviewCount'] === 1 ? '' : 's'; ?>)</span>
        </div>

        <div class="ez-product-card__pricing">
            <span class="ez-product-card__price"><?php echo format_price($m['price']); ?></span>
            <?php if ($m['hasDiscount']): ?>
            <span class="ez-product-card__original"><?php echo format_price($m['originalPrice']); ?></span>
            <?php if ($m['discountPct'] >= 5): ?>
            <span class="ez-product-card__discount-tag"><?php echo $m['discountPct']; ?>% OFF</span>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <p class="ez-product-card__save<?php echo !$m['hasDiscount'] ? ' is-empty' : ''; ?>">
            <?php if ($m['hasDiscount']): ?>Save <?php echo format_price($m['saveAmount']); ?><?php endif; ?>
        </p>

        <div class="ez-product-card__meta">
            <span class="ez-product-card__sold<?php echo $m['soldCount'] <= 0 ? ' is-empty' : ''; ?>">
                <?php if ($m['soldCount'] > 0): ?>
                <i class="fas fa-fire"></i> <?php echo number_format($m['soldCount']); ?> Sold
                <?php endif; ?>
            </span>
            <?php
            $stockLabel = '';
            $stockClass = 'ez-product-card__stock';
            if ($m['stock'] <= 0) {
                $stockLabel = 'Out of Stock';
                $stockClass .= ' ez-product-card__stock--out';
            } elseif ($m['stock'] <= 50) {
                $stockLabel = 'Only ' . number_format($m['stock']) . ' Left';
            }
            ?>
            <span class="<?php echo $stockClass; ?><?php echo $stockLabel === '' ? ' is-empty' : ''; ?>">
                <?php echo htmlspecialchars($stockLabel); ?>
            </span>
        </div>

        <div class="ez-product-card__actions">
            <?php if ($m['stock'] > 0 && !$m['hasVariations']): ?>
            <form action="<?php echo BASE_URL; ?>controllers/CartController.php" method="POST" class="ez-product-card__cart-form" data-card-overlay>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo $m['productId']; ?>">
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($m['redirectUrl']); ?>">
                <button type="submit" class="ez-product-card__btn ez-product-card__btn--cart">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
            </form>
            <form action="<?php echo BASE_URL; ?>controllers/CartController.php" method="POST" class="ez-product-card__cart-form" data-card-overlay>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo $m['productId']; ?>">
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="redirect_url" value="<?php echo BASE_URL; ?>checkout.php">
                <button type="submit" class="ez-product-card__btn ez-product-card__btn--buy">
                    <i class="fas fa-bolt"></i> Buy Now
                </button>
            </form>
            <?php elseif ($m['stock'] > 0): ?>
            <a href="<?php echo htmlspecialchars($productUrl); ?>" class="ez-product-card__btn ez-product-card__btn--cart ez-product-card__btn--full">
                <i class="fas fa-sliders-h"></i> Select Options
            </a>
            <?php else: ?>
            <a href="<?php echo htmlspecialchars($productUrl); ?>" class="ez-product-card__btn ez-product-card__btn--cart ez-product-card__btn--full">
                <i class="fas fa-eye"></i> View Product
            </a>
            <?php endif; ?>
        </div>
    </div>
</article>
