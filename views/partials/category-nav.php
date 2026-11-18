<?php
// Fetch categories and subcategories for nav bar
if (!class_exists('CategoryDAL')) {
    require_once __DIR__ . '/../../dal/CategoryDAL.php';
}
if (!class_exists('SubcategoryDAL')) {
    require_once __DIR__ . '/../../dal/SubcategoryDAL.php';
}
if (!class_exists('ProductDAL')) {
    require_once __DIR__ . '/../../dal/ProductDAL.php';
}
try {
    $navCategoryDAL = new CategoryDAL();
    $navSubcategoryDAL = new SubcategoryDAL();
    $navProductDAL = new ProductDAL();
    $navCategories = $navCategoryDAL->getActive();
    
    // Get subcategories for each category, fallback to products if none
    $navCatProducts = [];
    foreach ($navCategories as $navCat) {
        $subs = $navSubcategoryDAL->getByCategory($navCat['id']);
        if (!empty($subs)) {
            $navCatProducts[$navCat['id']] = ['type' => 'subcategories', 'items' => $subs];
        } else {
            $navCatProducts[$navCat['id']] = ['type' => 'products', 'items' => $navProductDAL->getByCategory($navCat['id'])];
        }
    }
} catch (Exception $e) {
    $navCategories = [];
    $navCatProducts = [];
}
$firstFive = array_slice($navCategories, 0, 5);

$navScript = strtolower(basename(parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: ''));
$navCategorySlug = strtolower(trim((string)($_GET['category'] ?? '')));
$navIsHome = in_array($navScript, ['index.php', ''], true);
$navIsAbout = ($navScript === 'about.php');
$navIsContact = ($navScript === 'contact.php');
$navIsShop = ($navScript === 'shop.php');
?>
<!-- Category Navigation Bar -->
<div class="category-nav">
    <div class="container category-nav-content">
        <div class="all-categories-wrapper">
            <button class="all-categories-btn" id="allCatBtn">
                <i class="fas fa-bars"></i> All Categories <i class="fas fa-chevron-down cat-arrow"></i>
            </button>
            <!-- Mega Dropdown -->
            <div class="all-categories-dropdown" id="allCatDropdown">
                <div class="cat-mega-menu">
                    <!-- Left: Categories -->
                    <ul class="cat-dropdown-list" id="catList">
                        <?php foreach ($navCategories as $index => $navCat):
                            $dropdownActive = ($navIsShop && $navCategorySlug !== '' && strtolower((string)$navCat['slug']) === $navCategorySlug);
                        ?>
                        <li data-cat-id="<?php echo $navCat['id']; ?>" class="<?php echo ($dropdownActive || (!$navCategorySlug && $index === 0)) ? 'active' : ''; ?>">
                            <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo htmlspecialchars($navCat['slug']); ?>">
                                <span><?php echo htmlspecialchars($navCat['name']); ?></span>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <!-- Right: Subcategories/Products of hovered category -->
                    <div class="cat-products-panel" id="catProductsPanel">
                        <?php foreach ($navCategories as $index => $navCat):
                            $panelActive = ($navIsShop && $navCategorySlug !== '' && strtolower((string)$navCat['slug']) === $navCategorySlug);
                            $showPanel = $panelActive || (!$navCategorySlug && $index === 0);
                        ?>
                        <ul class="cat-product-list" data-for-cat="<?php echo $navCat['id']; ?>" style="<?php echo $showPanel ? '' : 'display:none;'; ?>">
                            <?php if (!empty($navCatProducts[$navCat['id']]['items'])): ?>
                                <?php $data = $navCatProducts[$navCat['id']]; ?>
                                <?php if ($data['type'] === 'subcategories'): ?>
                                    <?php foreach ($data['items'] as $sub): ?>
                                    <li>
                                        <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo htmlspecialchars($navCat['slug']); ?>&subcategory=<?php echo htmlspecialchars($sub['slug']); ?>">
                                            <span><?php echo htmlspecialchars($sub['name']); ?></span>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($data['items'] as $navProd): ?>
                                    <li>
                                        <a href="<?php echo BASE_URL; ?>product.php?slug=<?php echo htmlspecialchars($navProd['slug']); ?>">
                                            <span><?php echo htmlspecialchars($navProd['name']); ?></span>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <li class="no-products"><span>No items in this category</span></li>
                            <?php endif; ?>
                        </ul>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <nav class="category-links" aria-label="Main categories">
            <a href="<?php echo BASE_URL; ?>" class="cat-link<?php echo $navIsHome ? ' active' : ''; ?>">Home</a>
            <?php foreach ($firstFive as $navCat):
                $catActive = $navIsShop && $navCategorySlug !== '' && strtolower((string)$navCat['slug']) === $navCategorySlug;
            ?>
            <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo htmlspecialchars($navCat['slug']); ?>" class="cat-link<?php echo $catActive ? ' active' : ''; ?>">
                <?php echo htmlspecialchars($navCat['name']); ?>
            </a>
            <?php endforeach; ?>
            <a href="<?php echo BASE_URL; ?>about.php" class="cat-link<?php echo $navIsAbout ? ' active' : ''; ?>">About Us</a>
            <a href="<?php echo BASE_URL; ?>contact.php" class="cat-link<?php echo $navIsContact ? ' active' : ''; ?>">Contact Us</a>
        </nav>
    </div>
</div>

<script>
(function() {
    var btn = document.getElementById('allCatBtn');
    var dropdown = document.getElementById('allCatDropdown');
    var catItems = document.querySelectorAll('#catList li');
    var productLists = document.querySelectorAll('.cat-product-list');

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('show');
        btn.classList.toggle('active');
    });

    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
            dropdown.classList.remove('show');
            btn.classList.remove('active');
        }
    });

    // Hover on category shows its products
    catItems.forEach(function(item) {
        item.addEventListener('mouseenter', function() {
            var catId = this.getAttribute('data-cat-id');
            
            // Remove active from all
            catItems.forEach(function(i) { i.classList.remove('active'); });
            this.classList.add('active');
            
            // Hide all product lists, show the matching one
            productLists.forEach(function(list) { list.style.display = 'none'; });
            var target = document.querySelector('.cat-product-list[data-for-cat="' + catId + '"]');
            if (target) target.style.display = 'block';
        });
    });
})();
</script>
