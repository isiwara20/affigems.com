<?php
/**
 * Product card helpers — shared data + rendering for storefront grids.
 */

if (!function_exists('ezymart_build_product_card_context')) {
    /**
     * @param array<int, array<string, mixed>> $products
     * @return array<string, mixed>
     */
    function ezymart_build_product_card_context(array $products): array
    {
        $productIds = array_values(array_filter(array_map(static function ($p) {
            return (int)($p['id'] ?? 0);
        }, $products)));

        $ratings = [];
        $soldCounts = [];
        $wishlistIds = [];
        $freeShippingThreshold = 2500;

        try {
            if (!class_exists('ReviewDAL')) {
                require_once __DIR__ . '/../dal/ReviewDAL.php';
            }
            $reviewDAL = new ReviewDAL();
            $ratings = $reviewDAL->getAverageRatingsByProductIds($productIds);
        } catch (Exception $e) {
        }

        try {
            if (!class_exists('ProductDAL')) {
                require_once __DIR__ . '/../dal/ProductDAL.php';
            }
            $productDAL = new ProductDAL();
            $soldCounts = $productDAL->getSoldCountsByProductIds($productIds);
        } catch (Exception $e) {
        }

        try {
            if (!class_exists('SiteSettingsDAL')) {
                require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
            }
            $settingsDAL = new SiteSettingsDAL();
            $freeShippingThreshold = (int)$settingsDAL->get('free_delivery_threshold', '2500');
        } catch (Exception $e) {
        }

        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && empty($_SESSION['is_admin'])) {
            try {
                if (!class_exists('WishlistDAL')) {
                    require_once __DIR__ . '/../dal/WishlistDAL.php';
                }
                $wishlistDAL = new WishlistDAL();
                $wishlistIds = $wishlistDAL->getProductIdsForUser((int)$_SESSION['user_id']);
            } catch (Exception $e) {
            }
        }

        return [
            'ratings' => $ratings,
            'soldCounts' => $soldCounts,
            'wishlistIds' => array_flip($wishlistIds),
            'freeShippingThreshold' => $freeShippingThreshold,
        ];
    }
}

if (!function_exists('ezymart_product_card_metrics')) {
    /**
     * @param array<string, mixed> $prod
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    function ezymart_product_card_metrics(array $prod, array $context): array
    {
        $productId = (int)($prod['id'] ?? 0);
        $price = (float)($prod['price'] ?? 0);
        $originalPrice = isset($prod['original_price']) ? (float)$prod['original_price'] : 0;
        $hasDiscount = $originalPrice > $price && $originalPrice > 0;
        $discountPct = $hasDiscount ? (int)round((($originalPrice - $price) / $originalPrice) * 100) : 0;
        $saveAmount = $hasDiscount ? ($originalPrice - $price) : 0;
        $stock = max(0, (int)($prod['stock'] ?? 0));
        $soldCount = (int)($context['soldCounts'][$productId] ?? 0);
        $ratingRow = $context['ratings'][$productId] ?? ['avg_rating' => 0, 'total' => 0];
        $avgRating = $ratingRow['avg_rating'] ? round((float)$ratingRow['avg_rating'], 1) : 0;
        $reviewCount = (int)($ratingRow['total'] ?? 0);
        $inWishlist = isset($context['wishlistIds'][$productId]);
        $freeShipping = $context['freeShippingThreshold'] <= 0 || $price >= $context['freeShippingThreshold'];
        $hasVariations = !empty($prod['has_variations']);
        $redirectUrl = $_SERVER['REQUEST_URI'] ?? BASE_URL . 'shop.php';

        return compact(
            'productId',
            'price',
            'originalPrice',
            'hasDiscount',
            'discountPct',
            'saveAmount',
            'stock',
            'soldCount',
            'avgRating',
            'reviewCount',
            'inWishlist',
            'freeShipping',
            'hasVariations',
            'redirectUrl'
        );
    }
}
