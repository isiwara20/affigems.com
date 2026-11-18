<?php
/**
 * Shop Page - Browse all products
 */
$pageTitle = "Shop - ARIGATO.LK";
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/../dal/ProductDAL.php';
require_once __DIR__ . '/../dal/CategoryDAL.php';
require_once __DIR__ . '/../dal/ReviewDAL.php';
require_once __DIR__ . '/../dal/MegaDealDAL.php';
require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
require_once __DIR__ . '/../helpers/product-card.php';

$hotDealsEnabled = false;
$hasActiveHotDeals = false;
$showHotDealsMenu = false;
$isHotDealsView = false;
$selectedDeals = $_GET['deals'] ?? '';
$catInfo = null;
$subInfo = null;
$searchQuery = '';
$selectedCategory = '';
$selectedSubcategory = '';
$viewAllProducts = (($_GET['view'] ?? '') === 'all-products');

try {
    $productDAL = new ProductDAL();
    $categoryDAL = new CategoryDAL();
    $reviewDAL = new ReviewDAL();
    $settingsDAL = new SiteSettingsDAL();
    $hotDealsEnabled = $settingsDAL->get('mega_deals_enabled', '1') === '1';

    if ($hotDealsEnabled) {
        $megaDealDAL = new MegaDealDAL();
        $hasActiveHotDeals = !empty($megaDealDAL->getActiveDeals());
    }
    $showHotDealsMenu = $hotDealsEnabled && $hasActiveHotDeals;
    
    $categories = $categoryDAL->getActive();
    
    // Filter by category, hot deals, or search
    $selectedCategory = $_GET['category'] ?? '';
    $selectedSubcategory = $_GET['subcategory'] ?? '';
    $searchQuery = trim($_GET['search'] ?? '');
    
    if ($selectedDeals === 'hot-deals' && $showHotDealsMenu) {
        $products = $megaDealDAL->getActiveDealProducts();
        $catInfo = null;
        $isHotDealsView = true;
        $pageTitle = 'Hot Deals - ARIGATO.LK';
    } elseif ($searchQuery) {
        $products = $productDAL->search($searchQuery);
        $catInfo = null;
        $pageTitle = 'Search: ' . htmlspecialchars($searchQuery) . ' - ARIGATO.LK';
    } elseif ($selectedCategory) {
        $catInfo = $categoryDAL->getBySlug($selectedCategory);
        if ($catInfo) {
            // If subcategory filter is set, filter by subcategory
            if ($selectedSubcategory) {
                if (!class_exists('SubcategoryDAL')) { require_once __DIR__ . '/../dal/SubcategoryDAL.php'; }
                $subDAL = new SubcategoryDAL();
                $subInfo = $subDAL->getBySlug($selectedSubcategory);
                if ($subInfo) {
                    $products = $productDAL->getBySubcategory($subInfo['id']);
                    $pageTitle = htmlspecialchars($subInfo['name']) . ' - ' . htmlspecialchars($catInfo['name']) . ' - ARIGATO.LK';
                } else {
                    $products = $productDAL->getByCategory($catInfo['id']);
                    $pageTitle = htmlspecialchars($catInfo['name']) . ' - ARIGATO.LK';
                }
            } else {
                $products = $productDAL->getByCategory($catInfo['id']);
                $pageTitle = htmlspecialchars($catInfo['name']) . ' - ARIGATO.LK';
            }
        } else {
            $products = $productDAL->getActive();
        }
    } else {
        $products = $productDAL->getActive();
        $catInfo = null;
        if ($viewAllProducts) {
            $pageTitle = 'All Products - ARIGATO.LK';
        }
    }
} catch (Exception $e) {
    $products = [];
    $categories = [];
    $catInfo = null;
    $subInfo = null;
    $searchQuery = '';
    $selectedCategory = '';
    $selectedSubcategory = '';
    $viewAllProducts = false;
    $hotDealsEnabled = false;
    $hasActiveHotDeals = false;
    $showHotDealsMenu = false;
    $isHotDealsView = false;
}
$isAllCategoriesView = empty($selectedCategory) && empty($searchQuery) && !$isHotDealsView && !$viewAllProducts;
$isAllProductsView = $viewAllProducts && empty($selectedCategory) && empty($searchQuery) && !$isHotDealsView;
?>

<style>
/* Shop Page Styles */
.shop-section {
    padding: 35px 0 70px;
    background: #fff;
    min-height: 70vh;
}

.shop-layout {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 35px;
}

/* Shop Sidebar */
.shop-sidebar {
    position: sticky;
    top: 130px;
    align-self: start;
}

.sidebar-card {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 22px;
    margin-bottom: 18px;
}

.sidebar-card h3 {
    font-size: 14px;
    font-weight: 700;
    color: #0d1137;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e8600a;
}

.sidebar-cat-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-cat-list li a {
    display: block;
    padding: 9px 12px;
    color: #555;
    text-decoration: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
    margin-bottom: 1px;
    border-left: 3px solid transparent;
}

.sidebar-cat-list li a:hover {
    background: #fafafa;
    color: #e8600a;
    border-left-color: #e8600a;
}

.sidebar-cat-list li a.active {
    background: #fff7f0;
    color: #e8600a;
    font-weight: 600;
    border-left-color: #e8600a;
}

.sidebar-cat-list li a.hot-deals-link {
    color: #ef4444;
    font-weight: 600;
}

.sidebar-cat-list li a.hot-deals-link:hover,
.sidebar-cat-list li a.hot-deals-link.active {
    background: #fff5f5;
    color: #dc2626;
    border-left-color: #ef4444;
}

.sidebar-cat-list li a.hot-deals-link i {
    margin-right: 6px;
    font-size: 11px;
}

.sidebar-subcat-list {
    list-style: none;
    padding: 0;
    margin: 0 0 4px 16px;
}

.sidebar-subcat-list li a {
    display: block;
    padding: 6px 12px;
    color: #777;
    text-decoration: none;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.2s;
    border-left: 2px solid #eee;
}

.sidebar-subcat-list li a:hover {
    color: #2d6b4f;
    background: #f4f9f6;
    border-left-color: #2d6b4f;
}

.sidebar-subcat-list li a.active {
    color: #2d6b4f;
    background: #f0f9f4;
    font-weight: 600;
    border-left-color: #2d6b4f;
}

.sidebar-help {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 22px;
    text-align: center;
}

.sidebar-help h4 {
    font-size: 14px;
    font-weight: 600;
    color: #0d1137;
    margin-bottom: 6px;
}

.sidebar-help p {
    font-size: 12px;
    color: #777;
    margin-bottom: 14px;
    line-height: 1.5;
}

.btn-whatsapp {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    background: #fff;
    color: #25d366;
    border: 1.5px solid #25d366;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-whatsapp:hover {
    background: #25d366;
    color: #fff;
}

/* Product Grid */
.shop-main {
    min-width: 0;
}

.shop-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.shop-header h2 {
    font-size: 18px;
    font-weight: 700;
    color: #0d1137;
}

.shop-header .result-count {
    font-size: 12px;
    color: #999;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
}

/* Product card styles live in assets/css/style.css (.ez-product-card) */

/* Shop page — wider cards, tighter product grid */
.shop-products-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
}

@media (max-width: 1200px) {
    .shop-products-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .shop-products-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
}

@media (max-width: 480px) {
    .shop-products-grid {
        grid-template-columns: 1fr;
        gap: 14px;
    }
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    grid-column: 1 / -1;
}

.empty-state i {
    font-size: 3.5rem;
    color: #ddd;
    margin-bottom: 16px;
}

.empty-state h3 {
    color: #0d1137;
    margin-bottom: 8px;
    font-size: 16px;
}

.empty-state p {
    color: #999;
    font-size: 13px;
}

/* Subcategory Cards */
.subcat-section-title {
    margin-bottom: 16px;
}

.subcat-section-title span {
    font-size: 15px;
    font-weight: 700;
    color: #0f2b1d;
}

.subcategory-cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.subcategory-card {
    border-radius: 14px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s;
    position: relative;
}

.subcategory-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(15, 43, 29, 0.12);
}

.subcategory-card-img {
    height: 200px;
    background: #f8faf9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

.subcategory-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.subcategory-card:hover .subcategory-card-img img {
    transform: scale(1.08);
}

.subcategory-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px 16px 16px;
    background: linear-gradient(transparent, rgba(15, 43, 29, 0.85));
    color: #fff;
    text-align: center;
}

.subcategory-overlay h4 {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 2px;
    color: #fff;
}

.subcategory-cat-label {
    font-size: 10px;
    color: rgba(255,255,255,0.7);
    display: block;
    margin-bottom: 4px;
}

.subcategory-explore {
    font-size: 11px;
    color: rgba(255,255,255,0.85);
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: gap 0.2s;
}

.subcategory-card:hover .subcategory-explore {
    gap: 8px;
    color: #fff;
}

.subcategory-placeholder {
    font-size: 2.5rem;
    color: #ddd;
}

.shop-divider {
    height: 1px;
    background: #e8f0ec;
    margin: 8px 0 24px;
}

/* Category cards (All Categories view) */
.shop-categories-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.shop-category-card {
    display: block;
    text-decoration: none;
    border-radius: 16px;
    overflow: hidden;
    position: relative;
    height: 260px;
    transition: all 0.3s;
    border: 1px solid #eee;
}

.shop-category-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 28px rgba(0, 0, 0, 0.12);
}

.shop-category-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    position: absolute;
    top: 0;
    left: 0;
    transition: transform 0.4s;
}

.shop-category-card:hover img {
    transform: scale(1.05);
}

.shop-category-card-icon {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 60px;
    color: #ddd;
    background: #f0f2f5;
}

.shop-category-card-bottom {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
    z-index: 2;
}

.shop-category-card-name {
    font-size: 16px;
    font-weight: 700;
    color: #fff;
    display: block;
    margin-bottom: 4px;
}

.shop-category-card-link {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.85);
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.shop-categories-grid .empty-state {
    grid-column: 1 / -1;
}

/* Responsive */
@media (max-width: 1200px) {
    .products-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .subcategory-cards-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .shop-categories-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 992px) {
    .shop-layout {
        grid-template-columns: 1fr;
    }
    .shop-main {
        order: 1;
    }
    .shop-sidebar {
        position: static;
        display: flex;
        flex-direction: row;
        gap: 14px;
        order: 2;
    }
    .shop-sidebar > * {
        flex: 1;
    }
    .products-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .shop-sidebar {
        display: none;
    }
    .shop-layout {
        grid-template-columns: 1fr;
    }
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .subcategory-cards-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .shop-categories-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .shop-category-card {
        height: 200px;
    }
    .subcategory-card-img {
        height: 150px;
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
}
</style>

<!-- Breadcrumb -->
<div class="breadcrumb-section">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>">Home</a>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <?php if ($isHotDealsView || $catInfo || $searchQuery || $isAllProductsView): ?>
            <a href="<?php echo BASE_URL; ?>shop.php">Shop</a>
            <?php else: ?>
            <span class="breadcrumb-current">Shop</span>
            <?php endif; ?>

            <?php if ($isAllProductsView): ?>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="breadcrumb-current">All Products</span>
            <?php elseif ($isHotDealsView): ?>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="breadcrumb-current">Hot Deals</span>
            <?php elseif ($searchQuery): ?>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="breadcrumb-current">Search: <?php echo htmlspecialchars($searchQuery); ?></span>
            <?php elseif ($catInfo): ?>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <?php if (!empty($subInfo)): ?>
            <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo htmlspecialchars($catInfo['slug']); ?>"><?php echo htmlspecialchars($catInfo['name']); ?></a>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="breadcrumb-current"><?php echo htmlspecialchars($subInfo['name']); ?></span>
            <?php else: ?>
            <span class="breadcrumb-current"><?php echo htmlspecialchars($catInfo['name']); ?></span>
            <?php endif; ?>
            <?php endif; ?>
        </nav>
    </div>
</div>

<!-- Shop Section -->
<section class="shop-section">
    <div class="container">
        <div class="shop-layout">
            <!-- Sidebar -->
            <aside class="shop-sidebar">
                <div class="sidebar-card">
                    <h3>All Categories</h3>
                    <ul class="sidebar-cat-list">
                        <li>
                            <a href="<?php echo BASE_URL; ?>shop.php" class="<?php echo empty($selectedCategory) && !$isHotDealsView ? 'active' : ''; ?>">
                                <span>All Categories</span>
                            </a>
                        </li>
                        <?php if ($showHotDealsMenu): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>shop.php?deals=hot-deals"
                               class="hot-deals-link <?php echo $isHotDealsView ? 'active' : ''; ?>">
                                <span><i class="fas fa-fire"></i> Hot Deals</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>" 
                               class="<?php echo ($selectedCategory === $cat['slug']) ? 'active' : ''; ?>">
                                <span><?php echo htmlspecialchars($cat['name']); ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="sidebar-help">
                    <h4>Need Help?</h4>
                    <p>Have a question about this product? Contact us.</p>
                    <a href="https://wa.me/<?php echo STORE_WHATSAPP; ?>" target="_blank" rel="noopener noreferrer" class="btn-whatsapp">
                        <i class="fab fa-whatsapp"></i> WhatsApp Us
                    </a>
                </div>
            </aside>

            <!-- Products Grid -->
            <div class="shop-main">
                <div class="shop-header">
                    <h2><?php
                        if ($isHotDealsView) {
                            echo 'Hot Deals';
                        } elseif (!empty($subInfo)) {
                            echo htmlspecialchars($subInfo['name']);
                        } elseif ($catInfo) {
                            echo htmlspecialchars($catInfo['name']);
                        } elseif ($searchQuery) {
                            echo 'Search Results';
                        } elseif ($isAllProductsView) {
                            echo 'All Products';
                        } else {
                            echo 'All Categories';
                        }
                    ?></h2>
                    <?php if ($isHotDealsView || $isAllProductsView || $catInfo || !empty($selectedSubcategory)): ?>
                    <span class="result-count"><?php echo count($products); ?> product(s) found</span>
                    <?php endif; ?>
                </div>

                <?php
                // Show subcategories if category is selected and has subcategories (and no subcategory filter is active)
                $showSubcats = [];
                if (!class_exists('SubcategoryDAL')) { require_once __DIR__ . '/../dal/SubcategoryDAL.php'; }
                $shopSubDAL = new SubcategoryDAL();
                
                if ($catInfo && empty($selectedSubcategory)) {
                    $showSubcats = $shopSubDAL->getByCategory($catInfo['id']);
                }
                ?>

                <?php if ($isAllCategoriesView): ?>
                <div class="shop-categories-grid">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                        <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>" class="shop-category-card">
                            <?php if (!empty($cat['image'])): ?>
                            <img src="<?php echo htmlspecialchars(media_image_url($cat['image'])); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>" loading="lazy">
                            <?php else: ?>
                            <div class="shop-category-card-icon"><i class="fas fa-microchip"></i></div>
                            <?php endif; ?>
                            <div class="shop-category-card-bottom">
                                <span class="shop-category-card-name"><?php echo htmlspecialchars($cat['name']); ?></span>
                                <span class="shop-category-card-link">Shop Now <i class="fas fa-arrow-right"></i></span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>No categories found</h3>
                        <p>Categories will appear here once they are added by the admin.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <?php elseif (!empty($showSubcats) && $catInfo): ?>
                <div class="subcat-section-title">
                    <span>Browse by Collection</span>
                </div>
                <div class="subcategory-cards-grid">
                    <?php foreach ($showSubcats as $subItem): ?>
                    <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo htmlspecialchars($catInfo['slug']); ?>&subcategory=<?php echo htmlspecialchars($subItem['slug']); ?>" class="subcategory-card">
                        <div class="subcategory-card-img">
                            <?php if (!empty($subItem['image'])): ?>
                            <img src="<?php echo htmlspecialchars(media_image_url($subItem['image'])); ?>" alt="<?php echo htmlspecialchars($subItem['name']); ?>" loading="lazy">
                            <?php else: ?>
                            <div class="subcategory-placeholder"><i class="fas fa-tshirt"></i></div>
                            <?php endif; ?>
                            <div class="subcategory-overlay">
                                <h4><?php echo htmlspecialchars($subItem['name']); ?></h4>
                                <span class="subcategory-explore">Explore <i class="fas fa-arrow-right"></i></span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php 
                // When subcategories are displayed, show only 4 random products below
                shuffle($products);
                $products = array_slice($products, 0, 4);
                ?>
                <div class="subcat-section-title" style="margin-top:30px;">
                    <span>Featured Products</span>
                </div>
                <?php endif; ?>

                <?php if (!$isAllCategoriesView && ($isAllProductsView || empty($showSubcats) || $catInfo || !empty($selectedSubcategory) || !empty($searchQuery) || $isHotDealsView)): ?>
                <div class="products-grid shop-products-grid">
                    <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>No products found</h3>
                        <p>Products will appear here once they are added by the admin.</p>
                    </div>
                    <?php else: ?>
                    <?php
                    $cardContext = ezymart_build_product_card_context($products);
                    foreach ($products as $prod):
                        $cardBadge = null;
                        if ($isHotDealsView) {
                            $cardBadge = 'hot-deal';
                        } elseif (!empty($prod['featured'])) {
                            $cardBadge = 'bestseller';
                        }
                        include __DIR__ . '/partials/product-card.php';
                    endforeach;
                    ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($products)): ?>
                <?php require_once __DIR__ . '/partials/product-quick-view.php'; ?>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
