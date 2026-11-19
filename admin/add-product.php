<?php
/**
 * Admin Add/Edit Product
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dal/ProductVariationDAL.php';
require_once __DIR__ . '/../dal/SubcategoryDAL.php';
require_once __DIR__ . '/../helpers/restock.php';

$warrantyHelper = __DIR__ . '/../helpers/warranty.php';
if (is_file($warrantyHelper)) {
    require_once $warrantyHelper;
}

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

if (!function_exists('ezymart_warranty_period_options')) {
    function ezymart_warranty_period_options(): array
    {
        return [
            '' => 'No warranty',
            '3_months' => '3 Months',
            '6_months' => '6 Months',
            '1_year' => '1 Year',
            '2_years' => '2 Years',
            '3_years' => '3 Years',
        ];
    }
}

$warrantyPeriodOptions = ezymart_warranty_period_options();

$database = new Database();
$conn = $database->getConnection();
$variationDAL = new ProductVariationDAL();

try {
    $conn->exec("ALTER TABLE products ADD COLUMN cost_price DECIMAL(10,2) NULL AFTER original_price");
} catch (Exception $e) {}
try {
    $conn->exec("ALTER TABLE products ADD COLUMN warranty_period VARCHAR(20) NULL DEFAULT NULL AFTER stock");
} catch (Exception $e) {}

// Ensure upload directory exists
$uploadDir = __DIR__ . '/../assets/uploads/products/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$message = '';
$messageType = '';
$editProduct = null;
$isEdit = false;

// Load product for editing
if (isset($_GET['id'])) {
    $isEdit = true;
    $editId = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $editId, PDO::PARAM_INT);
    $stmt->execute();
    $editProduct = $stmt->fetch();
    
    if (!$editProduct) {
        header('Location: ' . BASE_URL . 'admin/products.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $subcategoryId = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
    $description = trim($_POST['description'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $originalPrice = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
    $costPrice = isset($_POST['cost_price']) && $_POST['cost_price'] !== '' ? (float)$_POST['cost_price'] : null;
    $stock = (int)($_POST['stock'] ?? 0);
    $warrantyPeriod = trim($_POST['warranty_period'] ?? '');
    $warrantyOptions = array_keys($warrantyPeriodOptions);
    if (!in_array($warrantyPeriod, $warrantyOptions, true)) {
        $warrantyPeriod = '';
    }
    $author = trim($_POST['author'] ?? ADMIN_BRAND);
    $status = $_POST['status'] ?? 'active';
    $featured = isset($_POST['featured']) ? 1 : 0;
    $hasVariations = isset($_POST['has_variations']) ? 1 : 0;
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    
    // Handle image upload
    $imagePath = $editProduct['image'] ?? '';
    
    // Check if main image should be removed
    if (isset($_POST['remove_main_image']) && $_POST['remove_main_image'] === '1') {
        if ($imagePath) {
            $oldImage = __DIR__ . '/../' . $imagePath;
            if (file_exists($oldImage)) unlink($oldImage);
        }
        $imagePath = '';
    }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $fileType = $_FILES['image']['type'];
        $fileSize = $_FILES['image']['size'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $message = 'Invalid image type. Allowed: JPG, PNG, WEBP';
            $messageType = 'error';
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $message = 'Image size must be less than 5MB.';
            $messageType = 'error';
        } else {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = 'product_' . time() . '_' . uniqid() . '.' . $ext;
            $destination = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                // Optimize image (compress + resize + convert to WebP)
                require_once __DIR__ . '/../services/ImageOptimizer.php';
                $optimizedPath = ImageOptimizer::optimizeProduct($destination);
                $optimizedFileName = basename($optimizedPath);
                
                // Delete old image if updating
                if ($isEdit && $editProduct['image']) {
                    $oldImage = __DIR__ . '/../' . $editProduct['image'];
                    if (file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                }
                $imagePath = 'assets/uploads/products/' . $optimizedFileName;
            }
        }
    }

    // Handle gallery images upload
    $galleryPaths = [];
    // Keep existing gallery images that weren't removed
    if (isset($_POST['existing_gallery']) && !empty($_POST['existing_gallery'])) {
        $galleryPaths = json_decode($_POST['existing_gallery'], true) ?: [];
    }
    if (isset($_FILES['gallery']) && !empty($_FILES['gallery']['name'][0])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        foreach ($_FILES['gallery']['name'] as $key => $name_file) {
            if ($_FILES['gallery']['error'][$key] === UPLOAD_ERR_OK) {
                $gFileType = $_FILES['gallery']['type'][$key];
                $gFileSize = $_FILES['gallery']['size'][$key];
                
                if (in_array($gFileType, $allowedTypes) && $gFileSize <= 5 * 1024 * 1024) {
                    $gExt = pathinfo($name_file, PATHINFO_EXTENSION);
                    $gFileName = 'gallery_' . time() . '_' . uniqid() . '.' . $gExt;
                    $gDestination = $uploadDir . $gFileName;
                    
                    if (move_uploaded_file($_FILES['gallery']['tmp_name'][$key], $gDestination)) {
                        // Optimize gallery image
                        if (!class_exists('ImageOptimizer')) { require_once __DIR__ . '/../services/ImageOptimizer.php'; }
                        $gOptimized = ImageOptimizer::optimizeGallery($gDestination);
                        $galleryPaths[] = 'assets/uploads/products/' . basename($gOptimized);
                    }
                }
            }
        }
    }
    $galleryJson = !empty($galleryPaths) ? json_encode($galleryPaths) : null;
    
    if (empty($name) && empty($message)) {
        $message = 'Product name is required.';
        $messageType = 'error';
    } elseif ($categoryId === 0 && empty($message)) {
        $message = 'Please select a category.';
        $messageType = 'error';
    } elseif ($price <= 0 && empty($message) && !$hasVariations) {
        $message = 'Price must be greater than zero.';
        $messageType = 'error';
    } elseif (empty($message)) {
        $oldProductStock = $isEdit ? (int)($editProduct['stock'] ?? 0) : 0;
        $oldVariationStocks = $isEdit ? ezymart_restock_variation_stock_map((int)$editProduct['id']) : [];

        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE products SET 
                category_id = :category_id, subcategory_id = :subcategory_id, name = :name, slug = :slug, description = :description, 
                details = :details, price = :price, original_price = :original_price, cost_price = :cost_price, stock = :stock, warranty_period = :warranty_period,
                image = :image, images = :images, author = :author, status = :status, featured = :featured, has_variations = :has_variations
                WHERE id = :id");
            $stmt->bindParam(':id', $editProduct['id'], PDO::PARAM_INT);
        } else {
            $stmt = $conn->prepare("INSERT INTO products 
                (category_id, subcategory_id, name, slug, description, details, price, original_price, cost_price, stock, warranty_period, image, images, author, status, featured, has_variations) 
                VALUES (:category_id, :subcategory_id, :name, :slug, :description, :details, :price, :original_price, :cost_price, :stock, :warranty_period, :image, :images, :author, :status, :featured, :has_variations)");
        }
        
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        if ($subcategoryId === null) {
            $stmt->bindValue(':subcategory_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':subcategory_id', $subcategoryId, PDO::PARAM_INT);
        }
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':details', $details);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':original_price', $originalPrice);
        if ($costPrice === null) {
            $stmt->bindValue(':cost_price', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':cost_price', $costPrice);
        }
        $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
        if ($warrantyPeriod === '') {
            $stmt->bindValue(':warranty_period', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':warranty_period', $warrantyPeriod);
        }
        $stmt->bindParam(':image', $imagePath);
        $stmt->bindParam(':images', $galleryJson);
        $stmt->bindParam(':author', $author);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':featured', $featured, PDO::PARAM_INT);
        $stmt->bindParam(':has_variations', $hasVariations, PDO::PARAM_INT);
        
        try {
            if ($stmt->execute()) {
                $savedProductId = $isEdit ? (int)$editProduct['id'] : (int)$conn->lastInsertId();
                $optionsPayload = json_decode($_POST['variation_options_json'] ?? '[]', true) ?: [];
                $variationsPayload = json_decode($_POST['variation_rows_json'] ?? '[]', true) ?: [];
                $variationDAL->saveProductVariations($savedProductId, (bool)$hasVariations, $optionsPayload, $variationsPayload);

                $newProductRow = $conn->prepare('SELECT stock FROM products WHERE id = :id');
                $newProductRow->execute([':id' => $savedProductId]);
                $newProductStock = (int)($newProductRow->fetch()['stock'] ?? 0);
                $newVariationStocks = ezymart_restock_variation_stock_map($savedProductId);
                require_once __DIR__ . '/../services/AsyncMailQueue.php';
                ezymart_restock_check_and_notify(
                    $savedProductId,
                    $oldProductStock,
                    $newProductStock,
                    $oldVariationStocks,
                    $newVariationStocks
                );

                $_SESSION['admin_success'] = $isEdit ? 'Product updated successfully!' : 'Product created successfully!';
                header('Location: ' . BASE_URL . 'admin/products.php');
                AsyncMailQueue::flushAfterRedirect();
                exit;
            } else {
                $message = 'Failed to save product.';
                $messageType = 'error';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = 'A product with this name already exists. Please use a different name.';
            } else {
                $message = 'Failed to save product. Please try again.';
            }
            $messageType = 'error';
        }
    }
}

// Fetch categories for dropdown
$stmt = $conn->prepare("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build subcategory map for JS (must happen before HTML output so PHP warnings don't break scripts)
$subcatMap = [];
try {
    $subcatDAL = new SubcategoryDAL();
    $allSubs = $subcatDAL->getActive();
    foreach ($allSubs as $s) {
        $catKey = (string)(int)($s['category_id'] ?? 0);
        if (!isset($subcatMap[$catKey])) {
            $subcatMap[$catKey] = [];
        }
        $subcatMap[$catKey][] = [
            'id' => (int)$s['id'],
            'name' => (string)$s['name'],
        ];
    }
} catch (Throwable $e) {
    $subcatMap = [];
}
$subcatMapJson = json_encode($subcatMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
if ($subcatMapJson === false) {
    $subcatMapJson = '{}';
}

$variationData = ['has_variations' => false, 'options' => [], 'variations' => []];
if ($isEdit && $editProduct) {
    $variationData = $variationDAL->getProductVariationData((int)$editProduct['id']);
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title><?php echo $isEdit ? 'Edit Product' : 'Add Product'; ?> - Admin | <?php echo ADMIN_BRAND; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .gallery-preview-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
        .gallery-preview-item { position: relative; width: 80px; height: 80px; border-radius: 10px; overflow: hidden; border: 2px solid #e4e8f0; flex-shrink: 0; }
        .gallery-preview-item:hover { border-color: #ef4444; }
        .gallery-preview-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .gallery-remove-btn { position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; background: rgba(239,68,68,0.9); color: #fff; border: none; border-radius: 50%; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; }
        .gallery-preview-item:hover .gallery-remove-btn { opacity: 1; }
        .preview-img { max-width: 150px; border-radius: 10px; margin-top: 8px; border: 2px solid #f0f2f8; }
        .variation-panel { margin-top: 0; }
        .variation-panel-full {
            width: 100%;
            margin-top: 24px;
        }
        .product-form {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        .variation-option-row {
            display: grid;
            grid-template-columns: minmax(160px, 200px) minmax(0, 1fr) 44px;
            gap: 12px;
            align-items: start;
            margin-bottom: 12px;
            padding: 14px;
            background: #fafbfc;
            border: 1px solid #eef1f6;
            border-radius: 12px;
        }
        .variation-option-row .btn-secondary {
            width: 44px;
            height: 44px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            align-self: start;
        }
        .variation-skus-wrap {
            border: 1px solid #eef1f6;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }
        .variation-skus-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .variation-skus-table thead th {
            background: #f8f9fb;
            color: #666;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #eef1f6;
        }
        .variation-skus-table thead th:first-child {
            width: 42px;
            text-align: center;
        }
        .variation-skus-table thead th:nth-child(2) {
            width: auto;
            text-align: left;
        }
        .variation-skus-table thead th:nth-child(3),
        .variation-skus-table thead th:nth-child(4) {
            width: 110px;
        }
        .variation-skus-table thead th:nth-child(3) {
            text-align: right;
        }
        .variation-skus-table thead th:nth-child(4) {
            text-align: center;
        }
        .variation-skus-table thead th:nth-child(5) {
            width: 140px;
            text-align: left;
        }
        .variation-skus-table thead th:last-child {
            width: 52px;
            text-align: center;
        }
        .variation-skus-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #f3f4f8;
            vertical-align: middle;
        }
        .variation-skus-table tbody td:first-child {
            text-align: center;
            color: #aaa;
            font-size: 12px;
            font-weight: 600;
        }
        .variation-skus-table tbody td:nth-child(3) {
            text-align: right;
        }
        .variation-skus-table tbody td:nth-child(4) {
            text-align: center;
        }
        .variation-skus-table tbody td:nth-child(5) {
            text-align: left;
        }
        .variation-skus-table tbody tr:last-child td {
            border-bottom: none;
        }
        .variation-skus-table tbody tr:hover {
            background: #f8fafd;
        }
        .variation-skus-table tbody tr:nth-child(even) {
            background: #fcfcfd;
        }
        .variation-skus-table tbody tr:nth-child(even):hover {
            background: #f8fafd;
        }
        .variation-combo-cell {
            min-width: 0;
        }
        .variation-combo-display {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .variation-combo-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8f9fb;
            border: 1px solid #e4e8f0;
            border-radius: 8px;
            padding: 7px 11px;
            font-size: 12px;
            line-height: 1.2;
        }
        .variation-combo-badge-key {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #999;
            letter-spacing: 0.04em;
        }
        .variation-combo-badge-val {
            font-weight: 600;
            color: #222;
        }
        .variation-combo-badge--color {
            padding-left: 7px;
        }
        .variation-combo-swatch {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.12);
            flex-shrink: 0;
        }
        .variation-combo-editable {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .variation-combo-select {
            flex: 1 1 110px;
            min-width: 100px;
            font-size: 12px;
        }
        .variation-skus-table .variation-input-price {
            text-align: right;
        }
        .variation-skus-table .variation-input-stock {
            text-align: center;
        }
        .variation-skus-table .form-control {
            width: 100%;
            min-width: 0;
        }
        .variation-skus-table .btn-secondary {
            width: 38px;
            height: 38px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .variation-skus-empty {
            padding: 24px;
            text-align: center;
            color: #888;
            font-size: 13px;
        }
        .variation-toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0 0; }
        .variation-chip-list { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .variation-chip { background: #e8f0fe; color: #002868; border: 1px solid #dbe5f5; padding: 4px 10px; border-radius: 999px; font-size: 11px; }
        .btn-variation { border: 1px dashed #c4b5fd; background: #f8fafd; color: #002868; border-radius: 10px; padding: 8px 12px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .btn-variation:hover { background: #e8f0fe; }
        #variationsSection[hidden] { display: none !important; }
        .variation-guide {
            background: #f8fafd;
            border: 1px solid #dbe5f5;
            border-radius: 14px;
            padding: 16px 18px;
            margin-bottom: 18px;
        }
        .variation-guide h4 {
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: 700;
            color: #002868;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .variation-guide ol {
            margin: 0;
            padding-left: 20px;
            color: #555;
            font-size: 12px;
            line-height: 1.7;
        }
        .variation-guide li { margin-bottom: 4px; }
        .variation-guide strong { color: #333; }
        .variation-values-wrap { min-width: 0; }
        .color-picker-panel {
            border: 1px solid #ebe0ff;
            border-radius: 12px;
            padding: 12px;
            background: #fff;
        }
        .color-add-row {
            display: grid;
            grid-template-columns: 46px 1fr auto;
            gap: 8px;
            align-items: center;
            margin-bottom: 10px;
        }
        .color-add-row input[type="color"] {
            width: 46px;
            height: 40px;
            border: 1px solid #e4e8f0;
            border-radius: 10px;
            padding: 2px;
            cursor: pointer;
            background: #fff;
        }
        .color-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-height: 8px;
        }
        .color-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f8f9fb;
            border: 1px solid #e4e8f0;
            border-radius: 999px;
            padding: 5px 10px 5px 5px;
            font-size: 12px;
            font-weight: 600;
            color: #333;
        }
        .color-chip-swatch {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 0 1px rgba(0,0,0,0.08);
            flex-shrink: 0;
        }
        .color-chip button {
            border: none;
            background: none;
            color: #999;
            cursor: pointer;
            font-size: 14px;
            line-height: 1;
            padding: 0 2px;
        }
        .color-chip button:hover { color: #ef4444; }
        .variation-text-values { width: 100%; }
        .form-actions-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
            padding-top: 8px;
        }
        @media (max-width: 900px) {
            .variation-option-row {
                grid-template-columns: 1fr;
            }
            .variation-option-row .btn-secondary {
                width: 100%;
                height: 40px;
            }
            .variation-skus-wrap {
                overflow-x: auto;
            }
            .variation-skus-table {
                min-width: 720px;
            }
            .variation-combo-display {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php $currentPage = 'products'; include __DIR__ . '/partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php include __DIR__ . '/partials/topbar.php'; ?>

            <!-- Page Content -->
            <div class="dashboard-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title"><?php echo $isEdit ? 'Edit Product' : 'Add New Product'; ?></h1>
                        <p class="page-subtitle"><?php echo $isEdit ? 'Update product information' : 'Create a new product listing'; ?></p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>admin/products.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Product Form -->
                <form method="POST" enctype="multipart/form-data" class="product-form">
                    <div class="form-grid">
                        <!-- Left Column -->
                        <div class="form-column">
                            <div class="form-card">
                                <h3 class="form-card-title">Basic Information</h3>
                                
                                <div class="form-group">
                                    <label for="name">Product Name <span class="required">*</span></label>
                                    <input type="text" id="name" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>" 
                                           required oninput="generateSlug(this.value)">
                                </div>

                                <div class="form-group">
                                    <label for="slug_preview">Slug (auto-generated)</label>
                                    <input type="text" id="slug_preview" class="form-control" readonly 
                                           value="<?php echo htmlspecialchars($editProduct['slug'] ?? ''); ?>"
                                           style="background:#f0f0f0; color:#666;">
                                </div>
                                
                                <div class="form-group">
                                    <label for="category_id">Category <span class="required">*</span></label>
                                    <select id="category_id" name="category_id" class="form-control" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo (int)$cat['id']; ?>" 
                                            <?php echo (isset($editProduct['category_id']) && (int)$editProduct['category_id'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="subcategory_id">Subcategory <span class="required">*</span></label>
                                    <select id="subcategory_id" name="subcategory_id" class="form-control" required>
                                        <option value="">Select Subcategory</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description" class="form-control" rows="4"><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="details">Product Details</label>
                                    <textarea id="details" name="details" class="form-control" rows="4"><?php echo htmlspecialchars($editProduct['details'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="form-column">
                            <div class="form-card">
                                <h3 class="form-card-title">Pricing & Stock</h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="price">Price (LKR) <span class="required">*</span></label>
                                        <input type="number" id="price" name="price" class="form-control" step="0.01" min="0"
                                               value="<?php echo htmlspecialchars($editProduct['price'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="original_price">Original Price (LKR)</label>
                                        <input type="number" id="original_price" name="original_price" class="form-control" step="0.01" min="0"
                                               value="<?php echo htmlspecialchars($editProduct['original_price'] ?? ''); ?>">
                                        <small class="form-help">For showing discount (strikethrough)</small>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="cost_price">Cost Price (LKR)</label>
                                    <input type="number" id="cost_price" name="cost_price" class="form-control" step="0.01" min="0"
                                           value="<?php echo htmlspecialchars($editProduct['cost_price'] ?? ''); ?>">
                                    <small class="form-help">Admin only — not shown to customers. Used to calculate net profit.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="stock">Stock Quantity</label>
                                    <input type="number" id="stock" name="stock" class="form-control" min="0"
                                           value="<?php echo htmlspecialchars($editProduct['stock'] ?? '0'); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="warranty_period">Warranty Period</label>
                                    <select id="warranty_period" name="warranty_period" class="form-control">
                                        <?php foreach ($warrantyPeriodOptions as $value => $label): ?>
                                        <option value="<?php echo htmlspecialchars((string)$value); ?>" <?php echo (($editProduct['warranty_period'] ?? '') === $value) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-help">Warranty starts from the customer's purchase date.</small>
                                </div>
                            </div>

                            <div class="form-card">
                                <h3 class="form-card-title">Product Image</h3>
                                
                                <div class="form-group">
                                    <label for="image">Main Image</label>
                                    <input type="file" id="image" name="image" class="form-control-file" accept="image/jpeg,image/png,image/webp" onchange="previewMainImage(this)">
                                    <small class="form-help">Accepted: JPG, PNG, WEBP. Max 5MB.</small>
                                </div>
                                
                                <div class="main-image-preview" id="mainImagePreview">
                                    <?php if ($isEdit && $editProduct['image']): ?>
                                    <div class="gallery-preview-item" style="width:120px; height:120px;" id="mainImgThumb">
                                        <img src="<?php echo htmlspecialchars(media_image_url($editProduct['image'])); ?>" alt="">
                                        <button type="button" class="gallery-remove-btn" onclick="removeMainImage()" style="opacity:1;">&times;</button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="remove_main_image" id="removeMainImageFlag" value="0">

                                <div class="form-group" style="margin-top: 18px; padding-top: 18px; border-top: 1px solid #f0f0f0;">
                                    <label>Additional Images (Gallery)</label>
                                    <div class="gallery-upload-area">
                                        <input type="file" id="galleryInput" class="form-control-file" accept="image/jpeg,image/png,image/webp" multiple onchange="handleGallerySelect(this)">
                                        <small class="form-help">Select multiple images. Max 5MB each. You can add or remove before saving.</small>
                                    </div>
                                    <!-- Hidden inputs for actual form submission -->
                                    <div id="galleryHiddenInputs"></div>
                                    <!-- Preview Grid -->
                                    <div class="gallery-preview-grid" id="galleryPreview">
                                        <?php if ($isEdit && $editProduct['images']): ?>
                                            <?php foreach (json_decode($editProduct['images'], true) as $idx => $gImg): ?>
                                            <div class="gallery-preview-item" data-existing="<?php echo htmlspecialchars($gImg); ?>">
                                                <img src="<?php echo htmlspecialchars(media_image_url($gImg)); ?>" alt="">
                                                <button type="button" class="gallery-remove-btn" onclick="removeGalleryItem(this)">&times;</button>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Hidden field to track existing images to keep -->
                                    <input type="hidden" name="existing_gallery" id="existingGalleryInput" value="<?php echo htmlspecialchars($editProduct['images'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-card">
                                <h3 class="form-card-title">Additional Info</h3>
                                
                                <div class="form-group">
                                    <label for="author">Author</label>
                                    <input type="text" id="author" name="author" class="form-control" 
                                           value="<?php echo htmlspecialchars($editProduct['author'] ?? (defined('ADMIN_BRAND') ? ADMIN_BRAND : 'Affinity Gems & Jewels')); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="active" <?php echo (isset($editProduct['status']) && $editProduct['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo (isset($editProduct['status']) && $editProduct['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                
                                <div class="form-group checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="featured" value="1" 
                                               <?php echo (isset($editProduct['featured']) && $editProduct['featured']) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                        Mark as Featured
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-card variation-panel variation-panel-full">
                        <h3 class="form-card-title"><i class="fas fa-layer-group"></i> Product Variations</h3>
                        <p class="form-help" style="margin-bottom:14px;">Useful for clothes, gifts, drinkware, stationery, etc. (Size, Color, Capacity, Pack Quantity…)</p>

                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="hasVariations" name="has_variations" value="1"
                                    <?php echo !empty($variationData['has_variations']) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Enable product variations
                            </label>
                        </div>

                        <div id="variationsSection" <?php echo empty($variationData['has_variations']) ? 'hidden' : ''; ?>>
                            <div class="variation-guide">
                                <h4><i class="fas fa-info-circle"></i> How to add variations</h4>
                                <ol>
                                    <li><strong>Step 1:</strong> Click <em>Add option</em> and choose a type (Size, Color, Capacity, etc.).</li>
                                    <li><strong>Step 2:</strong> Add values — for <strong>Color</strong>, use the color picker; for others, type values separated by commas (e.g. <code>S, M, L</code> or <code>350ml, 500ml</code>).</li>
                                    <li><strong>Step 3:</strong> Click <em>Generate all combinations</em> to create every size/color mix automatically.</li>
                                    <li><strong>Step 4:</strong> Set <strong>price</strong>, <strong>stock</strong>, and optional <strong>SKU</strong> for each combination.</li>
                                    <li><strong>Step 5:</strong> Save the product — customers will choose options on the product page before adding to cart.</li>
                                </ol>
                            </div>

                            <div class="form-group">
                                <label>Variation options</label>
                                <div id="variationOptionsList"></div>
                                <div class="variation-toolbar">
                                    <button type="button" class="btn-variation" id="addVariationOptionBtn"><i class="fas fa-plus"></i> Add option</button>
                                    <button type="button" class="btn-variation" id="generateVariationsBtn"><i class="fas fa-magic"></i> Generate all combinations</button>
                                </div>
                                <small class="form-help">Tip: A mug might use <strong>Capacity</strong> (350ml, 500ml). A T-shirt might use <strong>Size</strong> + <strong>Color</strong>.</small>
                            </div>

                            <div class="form-group">
                                <label>Variation SKUs</label>
                                <div class="variation-skus-wrap">
                                    <table class="variation-skus-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Combination</th>
                                                <th>Price (LKR)</th>
                                                <th>Stock</th>
                                                <th>SKU</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="variationRowsList"></tbody>
                                    </table>
                                    <div class="variation-skus-empty" id="variationRowsEmpty">No variation rows yet. Add options above, then click <strong>Generate all combinations</strong>.</div>
                                </div>
                                <div class="variation-toolbar">
                                    <button type="button" class="btn-variation" id="addVariationRowBtn"><i class="fas fa-plus"></i> Add variation row</button>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="variation_options_json" id="variationOptionsJson" value="">
                        <input type="hidden" name="variation_rows_json" id="variationRowsJson" value="">
                    </div>

                    <div class="form-actions-row">
                        <button type="submit" class="btn-primary btn-lg">
                            <i class="fas fa-save"></i> <?php echo $isEdit ? 'Update Product' : 'Save Product'; ?>
                        </button>
                        <a href="<?php echo BASE_URL; ?>admin/products.php" class="btn-secondary btn-lg">Cancel</a>
                    </div>
                </form>

                <!-- Footer -->
                <div class="admin-footer">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo ADMIN_BRAND; ?>. All rights reserved.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.querySelector('.admin-layout').classList.toggle('sidebar-collapsed');
    });

    // Auto-generate slug from name
    function generateSlug(value) {
        var slug = value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
        document.getElementById('slug_preview').value = slug;
    }

    // Main image preview with delete
    function previewMainImage(input) {
        if (input.files && input.files[0]) {
            if (input.files[0].size > 5 * 1024 * 1024) {
                alert('Image size must be less than 5MB.');
                input.value = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById('mainImagePreview');
                preview.innerHTML = '<div class="gallery-preview-item" style="width:120px; height:120px;" id="mainImgThumb">' +
                    '<img src="' + e.target.result + '" alt="">' +
                    '<button type="button" class="gallery-remove-btn" onclick="removeMainImage()" style="opacity:1;">&times;</button></div>';
                document.getElementById('removeMainImageFlag').value = '0';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeMainImage() {
        document.getElementById('mainImagePreview').innerHTML = '';
        document.getElementById('image').value = '';
        document.getElementById('removeMainImageFlag').value = '1';
    }

    // Gallery management (max 8 images)
    var galleryFiles = [];
    var MAX_GALLERY = 8;

    function handleGallerySelect(input) {
        var files = input.files;
        var preview = document.getElementById('galleryPreview');
        var currentCount = preview.querySelectorAll('.gallery-preview-item').length;
        
        for (var i = 0; i < files.length; i++) {
            if (currentCount + i >= MAX_GALLERY) {
                alert('Maximum ' + MAX_GALLERY + ' gallery images allowed.');
                break;
            }
            
            var file = files[i];
            if (file.size > 5 * 1024 * 1024) {
                alert(file.name + ' is too large. Max 5MB.');
                continue;
            }
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                alert(file.name + ' is not a valid image type.');
                continue;
            }

            (function(f) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var div = document.createElement('div');
                    div.className = 'gallery-preview-item';
                    div.innerHTML = '<img src="' + e.target.result + '" alt="">' +
                        '<button type="button" class="gallery-remove-btn" onclick="removeGalleryItem(this)">&times;</button>';
                    preview.appendChild(div);
                };
                reader.readAsDataURL(f);
                galleryFiles.push(f);
            })(file);
        }

        input.value = '';
        updateGalleryHiddenInputs();
    }

    function removeGalleryItem(btn) {
        var item = btn.closest('.gallery-preview-item');
        var existingPath = item.getAttribute('data-existing');
        
        if (existingPath) {
            var existingInput = document.getElementById('existingGalleryInput');
            var existing = [];
            try { existing = JSON.parse(existingInput.value) || []; } catch(e) { existing = []; }
            existing = existing.filter(function(p) { return p !== existingPath; });
            existingInput.value = existing.length > 0 ? JSON.stringify(existing) : '';
        } else {
            var newItems = Array.from(document.querySelectorAll('.gallery-preview-item:not([data-existing])'));
            var removeIdx = newItems.indexOf(item);
            if (removeIdx >= 0) {
                galleryFiles.splice(removeIdx, 1);
            }
        }
        
        item.remove();
        updateGalleryHiddenInputs();
    }

    function updateGalleryHiddenInputs() {
        var container = document.getElementById('galleryHiddenInputs');
        container.innerHTML = '';
        
        if (galleryFiles.length > 0) {
            var dt = new DataTransfer();
            galleryFiles.forEach(function(f) { dt.items.add(f); });
            var hiddenInput = document.createElement('input');
            hiddenInput.type = 'file';
            hiddenInput.name = 'gallery[]';
            hiddenInput.multiple = true;
            hiddenInput.style.display = 'none';
            hiddenInput.files = dt.files;
            container.appendChild(hiddenInput);
        }
    }

    // Subcategory loading
    var allSubcategories = <?php echo $subcatMapJson; ?>;
    var currentSubcatId = <?php echo (int)($editProduct['subcategory_id'] ?? 0); ?>;

    function loadSubcategories(categoryId) {
        var select = document.getElementById('subcategory_id');
        if (!select) return;
        select.innerHTML = '<option value="">Select Subcategory</option>';
        var key = String(categoryId == null ? '' : categoryId);
        if (key === '') return;

        var list = allSubcategories[key] || allSubcategories[String(parseInt(key, 10))] || [];
        if (!list || !list.length) {
            var empty = document.createElement('option');
            empty.value = '';
            empty.textContent = 'No subcategories for this category';
            empty.disabled = true;
            select.appendChild(empty);
            return;
        }

        list.forEach(function(sub) {
            var opt = document.createElement('option');
            opt.value = sub.id;
            opt.textContent = sub.name;
            if (parseInt(sub.id, 10) === parseInt(currentSubcatId, 10)) opt.selected = true;
            select.appendChild(opt);
        });
    }

    // Load subcategories on page load if a category is already selected
    (function() {
        var catSelect = document.getElementById('category_id');
        if (catSelect && catSelect.value !== '') loadSubcategories(catSelect.value);
        if (catSelect) {
            catSelect.addEventListener('change', function() {
                currentSubcatId = 0;
                loadSubcategories(this.value);
            });
        }
    })();

    // ===== Product Variations =====
    var PRESET_OPTIONS = <?php echo json_encode(ProductVariationDAL::PRESET_OPTIONS); ?>;
    var initialVariationData = <?php echo json_encode($variationData, JSON_UNESCAPED_UNICODE); ?>;
    var variationOptions = (initialVariationData.options || []).map(function(opt) {
        return { name: opt.name, values: opt.values || [] };
    });
    var variationRows = (initialVariationData.variations || []).map(function(row) {
        return {
            options: row.options || {},
            price: row.price !== null && row.price !== undefined ? row.price : '',
            stock: row.stock || 0,
            sku: row.sku || '',
            status: row.status || 'active'
        };
    });

    var hasVariationsEl = document.getElementById('hasVariations');
    var variationsSection = document.getElementById('variationsSection');
    var variationOptionsList = document.getElementById('variationOptionsList');
    var variationRowsList = document.getElementById('variationRowsList');

    function toggleVariationsSection() {
        if (hasVariationsEl.checked) {
            variationsSection.removeAttribute('hidden');
        } else {
            variationsSection.setAttribute('hidden', 'hidden');
        }
    }
    hasVariationsEl.addEventListener('change', toggleVariationsSection);

    function parseValues(text) {
        return text.split(',').map(function(v) { return v.trim(); }).filter(Boolean);
    }

    function isColorOption(name) {
        return String(name || '').trim().toLowerCase() === 'color';
    }

    function parseColorValue(value) {
        if (!value) return { label: '', hex: '#003b94' };
        if (value.indexOf('|') !== -1) {
            var parts = value.split('|');
            return { label: parts[0].trim(), hex: parts[1].trim() || '#003b94' };
        }
        if (/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(value)) {
            return { label: value.toUpperCase(), hex: value };
        }
        return { label: value, hex: '' };
    }

    function formatColorValue(label, hex) {
        var cleanLabel = String(label || '').trim();
        var cleanHex = String(hex || '').trim();
        if (cleanLabel && cleanHex) return cleanLabel + '|' + cleanHex;
        if (cleanHex) return cleanHex;
        return cleanLabel;
    }

    function syncOptionValuesFromRow(index) {
        var row = variationOptionsList.children[index];
        if (!row) return;
        var nameSelect = row.querySelector('.variation-option-name');
        var optName = nameSelect ? nameSelect.value : variationOptions[index].name;
        if (isColorOption(optName)) {
            return;
        }
        var valuesInput = row.querySelector('.variation-option-values');
        if (valuesInput) {
            variationOptions[index].values = parseValues(valuesInput.value);
        }
    }

    function renderColorChips(index, container) {
        container.innerHTML = '';
        (variationOptions[index].values || []).forEach(function(value, colorIndex) {
            var parsed = parseColorValue(value);
            var chip = document.createElement('span');
            chip.className = 'color-chip';
            chip.innerHTML =
                '<span class="color-chip-swatch" style="background:' + (parsed.hex || '#ccc') + ';"></span>' +
                '<span>' + (parsed.label || parsed.hex || value).replace(/</g, '&lt;') + '</span>' +
                '<button type="button" aria-label="Remove color">&times;</button>';
            chip.querySelector('button').addEventListener('click', function() {
                variationOptions[index].values.splice(colorIndex, 1);
                renderColorChips(index, container);
                renderVariationRows();
            });
            container.appendChild(chip);
        });
    }

    function renderVariationOptions() {
        variationOptionsList.innerHTML = '';
        variationOptions.forEach(function(opt, index) {
            var row = document.createElement('div');
            row.className = 'variation-option-row';
            var selectHtml = '<select class="form-control variation-option-name" data-index="' + index + '">';
            selectHtml += '<option value="">Custom option</option>';
            PRESET_OPTIONS.forEach(function(preset) {
                selectHtml += '<option value="' + preset + '"' + (opt.name === preset ? ' selected' : '') + '>' + preset + '</option>';
            });
            selectHtml += '</select>';

            var valuesWrap = document.createElement('div');
            valuesWrap.className = 'variation-values-wrap';

            if (isColorOption(opt.name)) {
                valuesWrap.innerHTML =
                    '<div class="color-picker-panel">' +
                        '<div class="color-add-row">' +
                            '<input type="color" class="variation-color-input" data-index="' + index + '" value="#003b94">' +
                            '<input type="text" class="form-control variation-color-label" data-index="' + index + '" placeholder="Color name (e.g. Purple, Pink)">' +
                            '<button type="button" class="btn-secondary variation-color-add" data-index="' + index + '"><i class="fas fa-plus"></i> Add</button>' +
                        '</div>' +
                        '<div class="color-chip-list variation-color-chips" data-index="' + index + '"></div>' +
                        '<small class="form-help" style="margin-top:8px;display:block;">Pick a color, enter a name, then click Add. Repeat for each color option.</small>' +
                    '</div>';
            } else {
                valuesWrap.innerHTML =
                    '<input type="text" class="form-control variation-option-values variation-text-values" data-index="' + index + '" placeholder="Values separated by comma (e.g. S, M, L)" value="' + (opt.values || []).join(', ').replace(/"/g, '&quot;') + '">';
            }

            row.appendChild(document.createRange().createContextualFragment(selectHtml));
            row.appendChild(valuesWrap);

            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn-secondary';
            removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
            removeBtn.addEventListener('click', function() { removeVariationOption(index); });
            row.appendChild(removeBtn);
            variationOptionsList.appendChild(row);

            var nameSelect = row.querySelector('.variation-option-name');
            if (opt.name && PRESET_OPTIONS.indexOf(opt.name) === -1) {
                var custom = document.createElement('option');
                custom.value = opt.name;
                custom.textContent = opt.name;
                custom.selected = true;
                nameSelect.appendChild(custom);
            }

            nameSelect.addEventListener('change', function() {
                syncOptionValuesFromRow(index);
                variationOptions[index].name = this.value || variationOptions[index].name;
                renderVariationOptions();
                renderVariationRows();
            });

            if (isColorOption(opt.name)) {
                var chips = row.querySelector('.variation-color-chips');
                renderColorChips(index, chips);
                var colorInput = row.querySelector('.variation-color-input');
                var labelInput = row.querySelector('.variation-color-label');
                var addBtn = row.querySelector('.variation-color-add');

                addBtn.addEventListener('click', function() {
                    var hex = colorInput.value;
                    var label = labelInput.value.trim();
                    if (!label) {
                        label = hex.toUpperCase();
                    }
                    var formatted = formatColorValue(label, hex);
                    if ((variationOptions[index].values || []).indexOf(formatted) !== -1) {
                        alert('This color is already added.');
                        return;
                    }
                    variationOptions[index].values = variationOptions[index].values || [];
                    variationOptions[index].values.push(formatted);
                    labelInput.value = '';
                    renderColorChips(index, chips);
                    renderVariationRows();
                });

                labelInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addBtn.click();
                    }
                });
            } else {
                var valuesInput = row.querySelector('.variation-option-values');
                valuesInput.addEventListener('input', function() {
                    variationOptions[index].values = parseValues(this.value);
                });
            }
        });
    }

    function getOptionDisplayValue(optionName, value) {
        if (isColorOption(optionName)) {
            return parseColorValue(value).label || value;
        }
        return value;
    }

    function renderComboCell(row, index) {
        var activeOptions = variationOptions.filter(function(opt) { return opt.name; });
        var allSet = activeOptions.length > 0 && activeOptions.every(function(opt) {
            return (row.options || {})[opt.name];
        });

        if (allSet) {
            var badges = activeOptions.map(function(opt) {
                var val = row.options[opt.name];
                if (isColorOption(opt.name)) {
                    var parsed = parseColorValue(val);
                    var label = parsed.label || parsed.hex || val;
                    if (parsed.hex && parsed.label && parsed.label.toUpperCase() === parsed.hex.toUpperCase()) {
                        label = parsed.hex.toUpperCase();
                    }
                    return '<span class="variation-combo-badge variation-combo-badge--color" title="Color: ' + label.replace(/"/g, '&quot;') + '">' +
                        '<span class="variation-combo-swatch" style="background:' + (parsed.hex || '#ccc') + ';"></span>' +
                        '<span class="variation-combo-badge-key">Color</span>' +
                        '<span class="variation-combo-badge-val">' + label.replace(/</g, '&lt;') + '</span></span>';
                }
                return '<span class="variation-combo-badge" title="' + opt.name.replace(/"/g, '&quot;') + ': ' + val.replace(/"/g, '&quot;') + '">' +
                    '<span class="variation-combo-badge-key">' + opt.name.replace(/</g, '&lt;') + '</span>' +
                    '<span class="variation-combo-badge-val">' + val.replace(/</g, '&lt;') + '</span></span>';
            }).join('');
            return '<div class="variation-combo-display">' + badges + '</div>';
        }

        var selects = activeOptions.map(function(opt) {
            return '<select class="form-control variation-row-option variation-combo-select" data-row="' + index + '" data-name="' + opt.name.replace(/"/g, '&quot;') + '">' +
                '<option value="">' + opt.name + '…</option>' +
                (opt.values || []).map(function(val) {
                    var display = getOptionDisplayValue(opt.name, val);
                    if (isColorOption(opt.name)) {
                        var parsed = parseColorValue(val);
                        display = (parsed.label && parsed.label !== parsed.hex ? parsed.label : parsed.hex || val);
                    }
                    return '<option value="' + val.replace(/"/g, '&quot;') + '"' + ((row.options || {})[opt.name] === val ? ' selected' : '') + '>' + display.replace(/</g, '&lt;') + '</option>';
                }).join('') +
                '</select>';
        }).join('');
        return '<div class="variation-combo-editable">' + (selects || '<small class="form-help">Add options first</small>') + '</div>';
    }

    function renderVariationRows() {
        variationRowsList.innerHTML = '';
        var emptyState = document.getElementById('variationRowsEmpty');
        var skusTable = variationRowsList.closest('.variation-skus-table');
        if (emptyState) {
            emptyState.style.display = variationRows.length ? 'none' : 'block';
        }
        if (skusTable) {
            skusTable.style.display = variationRows.length ? '' : 'none';
        }

        variationRows.forEach(function(row, index) {
            var rowEl = document.createElement('tr');
            rowEl.innerHTML =
                '<td class="variation-row-num">' + (index + 1) + '</td>' +
                '<td class="variation-combo-cell">' + renderComboCell(row, index) + '</td>' +
                '<td><input type="number" class="form-control variation-row-price variation-input-price" data-row="' + index + '" step="0.01" min="0" placeholder="0.00" aria-label="Price for row ' + (index + 1) + '" value="' + (row.price !== '' ? row.price : '') + '"></td>' +
                '<td><input type="number" class="form-control variation-row-stock variation-input-stock" data-row="' + index + '" min="0" aria-label="Stock for row ' + (index + 1) + '" value="' + (row.stock || 0) + '"></td>' +
                '<td><input type="text" class="form-control variation-row-sku" data-row="' + index + '" placeholder="SKU code" aria-label="SKU for row ' + (index + 1) + '" value="' + (row.sku || '').replace(/"/g, '&quot;') + '"></td>' +
                '<td style="text-align:center;"><button type="button" class="btn-secondary variation-row-delete" onclick="removeVariationRow(' + index + ')" aria-label="Remove row ' + (index + 1) + '"><i class="fas fa-trash"></i></button></td>';
            variationRowsList.appendChild(rowEl);

            rowEl.querySelectorAll('.variation-row-option').forEach(function(sel) {
                sel.addEventListener('change', function() {
                    var rowIndex = parseInt(this.getAttribute('data-row'), 10);
                    var optionName = this.getAttribute('data-name');
                    variationRows[rowIndex].options[optionName] = this.value;
                    variationRows[rowIndex].label = buildRowLabel(variationRows[rowIndex].options);
                });
            });
            rowEl.querySelector('.variation-row-price').addEventListener('input', function() {
                variationRows[parseInt(this.getAttribute('data-row'), 10)].price = this.value;
            });
            rowEl.querySelector('.variation-row-stock').addEventListener('input', function() {
                variationRows[parseInt(this.getAttribute('data-row'), 10)].stock = parseInt(this.value, 10) || 0;
            });
            rowEl.querySelector('.variation-row-sku').addEventListener('input', function() {
                variationRows[parseInt(this.getAttribute('data-row'), 10)].sku = this.value;
            });
        });
    }

    function buildRowLabel(options) {
        return Object.keys(options).map(function(key) {
            var val = options[key];
            if (isColorOption(key)) {
                return key + ': ' + getOptionDisplayValue(key, val);
            }
            return key + ': ' + val;
        }).join(' / ');
    }

    window.removeVariationOption = function(index) {
        variationOptions.splice(index, 1);
        renderVariationOptions();
        renderVariationRows();
    };
    window.removeVariationRow = function(index) {
        variationRows.splice(index, 1);
        renderVariationRows();
    };

    document.getElementById('addVariationOptionBtn').addEventListener('click', function() {
        variationOptions.push({ name: 'Size', values: [] });
        renderVariationOptions();
        renderVariationRows();
    });

    document.getElementById('addVariationRowBtn').addEventListener('click', function() {
        variationRows.push({ options: {}, price: '', stock: 0, sku: '', status: 'active' });
        renderVariationRows();
    });

    document.getElementById('generateVariationsBtn').addEventListener('click', function() {
        var hasError = false;
        variationOptions.forEach(function(opt, idx) {
            if (hasError) return;
            syncOptionValuesFromRow(idx);
            var row = variationOptionsList.children[idx];
            if (!row) return;
            var nameSelect = row.querySelector('.variation-option-name');
            var customName = nameSelect ? nameSelect.value : opt.name;
            if (!customName) {
                alert('Please choose an option name for row ' + (idx + 1));
                hasError = true;
                return;
            }
            opt.name = customName;
        });
        if (hasError) return;
        var activeOptions = variationOptions.filter(function(opt) { return opt.name && opt.values.length; });
        if (!activeOptions.length) {
            alert('Add at least one option with values.');
            return;
        }
        function cartesian(arr, prefix) {
            if (!arr.length) return [prefix || {}];
            var first = arr[0];
            var rest = arr.slice(1);
            var results = [];
            first.values.forEach(function(value) {
                var next = Object.assign({}, prefix || {});
                next[first.name] = value;
                results = results.concat(cartesian(rest, next));
            });
            return results;
        }
        var combos = cartesian(activeOptions, {});
        variationRows = combos.map(function(options) {
            return { options: options, price: '', stock: 0, sku: '', status: 'active' };
        });
        renderVariationOptions();
        renderVariationRows();
    });

    document.querySelector('.product-form').addEventListener('submit', function() {
        variationOptions.forEach(function(opt, idx) {
            syncOptionValuesFromRow(idx);
        });
        document.getElementById('variationOptionsJson').value = JSON.stringify(
            variationOptions.filter(function(opt) { return opt.name && opt.values.length; })
        );
        document.getElementById('variationRowsJson').value = JSON.stringify(
            variationRows.filter(function(row) {
                return Object.keys(row.options || {}).length > 0;
            })
        );
    });

    renderVariationOptions();
    renderVariationRows();
    toggleVariationsSection();
    </script>
</body>
</html>
