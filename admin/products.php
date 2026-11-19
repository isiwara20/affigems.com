<?php
/**
 * Admin Products Management
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dal/CategoryDAL.php';
require_once __DIR__ . '/../dal/SubcategoryDAL.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Initialize database
$database = new Database();
$conn = $database->getConnection();

// Ensure tables exist
$conn->exec("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->exec("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    details TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2) NULL,
    cost_price DECIMAL(10,2) NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255) NULL,
    images TEXT NULL,
    author VARCHAR(100) DEFAULT 'Affinity Gems & Jewels',
    status ENUM('active', 'inactive') DEFAULT 'active',
    featured TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

try {
    $conn->exec("ALTER TABLE products ADD COLUMN sort_order INT DEFAULT 0 AFTER featured");
} catch (Exception $e) {}
try {
    $conn->exec("ALTER TABLE products ADD COLUMN cost_price DECIMAL(10,2) NULL AFTER original_price");
} catch (Exception $e) {}

$message = '';
$messageType = '';
if (isset($_SESSION['admin_error'])) {
    $message = $_SESSION['admin_error'];
    $messageType = 'error';
    unset($_SESSION['admin_error']);
}

// Handle sort order update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    header('Content-Type: application/json');
    require_once __DIR__ . '/../dal/ProductDAL.php';
    $productDAL = new ProductDAL();
    $orders = json_decode($_POST['orders'] ?? '[]', true);
    if (!empty($orders)) {
        foreach ($orders as $item) {
            $productDAL->updateSortOrder((int)$item['id'], (int)$item['order']);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stock') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $newStock = (int)($_POST['new_stock'] ?? 0);
    if ($productId > 0) {
        $oldStmt = $conn->prepare('SELECT stock FROM products WHERE id = :id');
        $oldStmt->execute([':id' => $productId]);
        $oldStock = (int)($oldStmt->fetch()['stock'] ?? 0);

        $stmt = $conn->prepare('UPDATE products SET stock = :stock WHERE id = :id');
        $stmt->execute([':stock' => $newStock, ':id' => $productId]);

        if ($oldStock <= 0 && $newStock > 0) {
            require_once __DIR__ . '/../helpers/restock.php';
            require_once __DIR__ . '/../services/AsyncMailQueue.php';
            ezymart_restock_check_and_notify($productId, $oldStock, $newStock);
        }
    }
    // If AJAX request, just return OK
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') === false) {
        echo 'ok';
        if (function_exists('flush')) {
            flush();
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        if (class_exists('AsyncMailQueue')) {
            AsyncMailQueue::flush();
        }
        exit;
    }
    if (class_exists('AsyncMailQueue')) {
        header('Location: ' . BASE_URL . 'admin/products.php');
        AsyncMailQueue::flushAfterRedirect();
        exit;
    }
    header('Location: ' . BASE_URL . 'admin/products.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    
    // Get product image to delete file
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch();
    
    if ($product && $product['image']) {
        $imagePath = __DIR__ . '/../' . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $_SESSION['admin_success'] = 'Product deleted successfully!';
    } else {
        $_SESSION['admin_error'] = 'Failed to delete product.';
    }

    $redirectUrl = BASE_URL . 'admin/products.php';
    $query = [];
    if (!empty($_GET['category'])) $query['category'] = (int)$_GET['category'];
    if (!empty($_GET['subcategory'])) $query['subcategory'] = (int)$_GET['subcategory'];
    if (!empty($query)) $redirectUrl .= '?' . http_build_query($query);
    header('Location: ' . $redirectUrl);
    exit;
}

$categoryDAL = new CategoryDAL();
$subcategoryDAL = new SubcategoryDAL();
$categories = $categoryDAL->getAll();
$allSubcategories = $subcategoryDAL->getAll();

$filterCategory = (int)($_GET['category'] ?? 0);
$filterSubcategory = (int)($_GET['subcategory'] ?? 0);

// Fetch products with optional category/subcategory filters
$sql = "SELECT p.*, c.name as category_name, s.name as subcategory_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN subcategories s ON p.subcategory_id = s.id
        WHERE 1=1";
$params = [];

if ($filterCategory > 0) {
    $sql .= " AND p.category_id = :category_id";
    $params[':category_id'] = $filterCategory;
}
if ($filterSubcategory > 0) {
    $sql .= " AND p.subcategory_id = :subcategory_id";
    $params[':subcategory_id'] = $filterSubcategory;
}
$sql .= " ORDER BY p.sort_order ASC, p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Subcategories grouped by category for filter dropdown
$subcatMap = [];
foreach ($allSubcategories as $sub) {
    $subcatMap[$sub['category_id']][] = ['id' => $sub['id'], 'name' => $sub['name']];
}

$filterSubcategories = $filterCategory > 0 ? $subcategoryDAL->getAllByCategory($filterCategory) : [];

$filterCategoryName = '';
foreach ($categories as $c) {
    if ((int)$c['id'] === $filterCategory) {
        $filterCategoryName = $c['name'];
        break;
    }
}
$filterSubcategoryName = '';
foreach ($allSubcategories as $s) {
    if ((int)$s['id'] === $filterSubcategory) {
        $filterSubcategoryName = $s['name'];
        break;
    }
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Products - Admin | <?php echo ADMIN_BRAND; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .dataTables_wrapper .dataTables_filter input { border: 1.5px solid #e4e8f0; border-radius: 8px; padding: 8px 14px; font-size: 13px; outline: none; font-family: 'Poppins', sans-serif; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: #2563eb; }
        .dataTables_wrapper .dataTables_length select { border: 1.5px solid #e4e8f0; border-radius: 8px; padding: 6px 10px; font-size: 13px; font-family: 'Poppins', sans-serif; }
        .dataTables_wrapper .dataTables_info { font-size: 12px; color: #6b7099; padding-top: 12px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 8px !important; font-size: 12px; padding: 6px 12px !important; margin: 0 2px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #2563eb !important; color: #fff !important; border-color: #2563eb !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #f0f2f8 !important; color: #0d1137 !important; }
        .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length { margin-bottom: 16px; font-size: 13px; }
        .dataTables_wrapper { padding: 20px; }
        .product-filters { display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; padding: 18px 20px; border-bottom: 1px solid #f0f2f8; background: #fafbfc; }
        .product-filters .filter-group { display: flex; flex-direction: column; gap: 6px; min-width: 200px; }
        .product-filters label { font-size: 11px; font-weight: 600; color: #6b7099; text-transform: uppercase; letter-spacing: 0.3px; }
        .product-filters select { padding: 9px 12px; border: 1.5px solid #e4e8f0; border-radius: 8px; font-size: 13px; font-family: 'Poppins', sans-serif; background: #fff; min-width: 200px; }
        .product-filters select:focus { outline: none; border-color: #2563eb; }
        .product-filters .filter-actions { display: flex; gap: 8px; align-items: center; }
        .btn-filter { padding: 9px 16px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; display: inline-flex; align-items: center; gap: 6px; }
        .btn-filter:hover { background: #1d4ed8; }
        .btn-filter-clear { padding: 9px 16px; background: #fff; color: #6b7099; border: 1.5px solid #e4e8f0; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; font-family: 'Poppins', sans-serif; display: inline-flex; align-items: center; gap: 6px; }
        .btn-filter-clear:hover { background: #f0f2f8; color: #0d1137; }
        .filter-active-note { font-size: 12px; color: #2563eb; padding: 0 20px 14px; background: #fafbfc; }
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
                        <h1 class="page-title">Products</h1>
                        <p class="page-subtitle">Manage your product listings</p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>admin/add-product.php" class="btn-primary">
                        <i class="fas fa-plus"></i> Add Product
                    </a>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Products Table -->
                <div class="table-card">
                    <form method="GET" class="product-filters" id="productFilterForm">
                        <div class="filter-group">
                            <label for="filterCategory">Category</label>
                            <select name="category" id="filterCategory" onchange="loadFilterSubcategories(this.value)">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $filterCategory === (int)$cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="filterSubcategory">Subcategory</label>
                            <select name="subcategory" id="filterSubcategory">
                                <option value="">All Subcategories</option>
                                <?php foreach ($filterSubcategories as $sub): ?>
                                <option value="<?php echo $sub['id']; ?>" <?php echo $filterSubcategory === (int)$sub['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sub['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
                            <?php if ($filterCategory || $filterSubcategory): ?>
                            <a href="<?php echo BASE_URL; ?>admin/products.php" class="btn-filter-clear"><i class="fas fa-times"></i> Clear</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php if ($filterCategory || $filterSubcategory): ?>
                    <div class="filter-active-note">
                        <i class="fas fa-info-circle"></i>
                        Showing <?php echo count($products); ?> product(s)
                        <?php if ($filterCategoryName): ?>
                            in category <strong><?php echo htmlspecialchars($filterCategoryName); ?></strong>
                        <?php endif; ?>
                        <?php if ($filterSubcategoryName): ?>
                            under subcategory <strong><?php echo htmlspecialchars($filterSubcategoryName); ?></strong>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <table class="data-table" id="productsTable">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Price</th>
                                <th>Cost</th>
                                <th>Net Profit</th>
                                <th>Order</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productSortable">
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="12" class="text-center">No products found.<?php echo ($filterCategory || $filterSubcategory) ? ' Try changing the filters.' : ' Add your first product!'; ?></td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($products as $prod): ?>
                            <tr data-id="<?php echo $prod['id']; ?>">
                                <td style="cursor:grab; color:#bbb;"><i class="fas fa-grip-vertical"></i></td>
                                <td>
                                    <?php if ($prod['image']): ?>
                                    <img src="<?php echo htmlspecialchars(media_image_url($prod['image'])); ?>" alt="" class="table-img">
                                    <?php else: ?>
                                    <div class="table-img-placeholder"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($prod['name']); ?></strong>
                                    <?php if ($prod['featured']): ?>
                                    <span class="badge-bestseller">Featured</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($prod['category_name'] ?? 'Uncategorized'); ?></td>
                                <td><?php if (!empty($prod['subcategory_name'])): ?><?php echo htmlspecialchars($prod['subcategory_name']); ?><?php else: ?><span style="color:#aaa;">—</span><?php endif; ?></td>
                                <td>
                                    <strong>LKR <?php echo number_format($prod['price'], 2); ?></strong>
                                    <?php if ($prod['original_price'] && $prod['original_price'] > $prod['price']): ?>
                                    <br><small class="text-muted"><s>LKR <?php echo number_format($prod['original_price'], 2); ?></s></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($prod['cost_price']) && $prod['cost_price'] !== null && $prod['cost_price'] !== ''): ?>
                                    LKR <?php echo number_format((float)$prod['cost_price'], 2); ?>
                                    <?php else: ?>
                                    <span style="color:#aaa;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $sellPrice = (float)$prod['price'];
                                    $costPrice = isset($prod['cost_price']) && $prod['cost_price'] !== null && $prod['cost_price'] !== '' ? (float)$prod['cost_price'] : null;
                                    if ($costPrice !== null):
                                        $netProfit = $sellPrice - $costPrice;
                                        $profitClass = $netProfit >= 0 ? 'text-success' : 'text-danger';
                                    ?>
                                    <strong class="<?php echo $profitClass; ?>">LKR <?php echo number_format($netProfit, 2); ?></strong>
                                    <?php if ($sellPrice > 0): ?>
                                    <br><small class="text-muted"><?php echo number_format(($netProfit / $sellPrice) * 100, 1); ?>% margin</small>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <span style="color:#aaa;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="number" value="<?php echo (int)($prod['sort_order'] ?? 0); ?>" min="0" style="width:60px; padding:5px 8px; border:1.5px solid #e4e8f0; border-radius:6px; font-size:12px; text-align:center;" onchange="updateSingleOrder(<?php echo $prod['id']; ?>, this.value)">
                                </td>
                                <td>
                                    <input type="number" value="<?php echo $prod['stock']; ?>" min="0" 
                                        class="stock-input <?php echo $prod['stock'] <= 5 ? 'critical' : ($prod['stock'] <= 15 ? 'warning' : 'good'); ?>"
                                        onchange="updateStock(<?php echo $prod['id']; ?>, this.value)"
                                        style="width:60px; padding:5px 8px; border:1.5px solid #e4e8f0; border-radius:6px; font-family:'Poppins',sans-serif; font-size:12px; font-weight:600; text-align:center; cursor:pointer;">
                                </td>
                                <td>
                                    <span class="status <?php echo $prod['status']; ?>">
                                        <?php echo ucfirst($prod['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="<?php echo BASE_URL; ?>admin/add-product.php?id=<?php echo $prod['id']; ?>" class="btn-action edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this product?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                                        <button type="submit" class="btn-action delete" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="admin-footer">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo ADMIN_BRAND; ?>. All rights reserved.</p>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.querySelector('.admin-layout').classList.toggle('sidebar-collapsed');
    });

    $(document).ready(function() {
        $('#productsTable').DataTable({
            pageLength: 10,
            ordering: true,
            columnDefs: [
                { orderable: false, targets: [0, 6, 9] }
            ],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ products",
                paginate: { previous: "‹", next: "›" }
            }
        });
    });

    var allSubcategories = <?php echo json_encode($subcatMap); ?>;
    var selectedSubcategory = <?php echo (int)$filterSubcategory; ?>;

    function loadFilterSubcategories(categoryId, keepSelection) {
        var select = document.getElementById('filterSubcategory');
        select.innerHTML = '<option value="">All Subcategories</option>';
        if (categoryId && allSubcategories[categoryId]) {
            allSubcategories[categoryId].forEach(function(sub) {
                var opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.name;
                if (keepSelection && sub.id == selectedSubcategory) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });
        }
    }

    (function() {
        var catSelect = document.getElementById('filterCategory');
        if (catSelect.value) {
            loadFilterSubcategories(catSelect.value, true);
        }
    })();

    function updateSingleOrder(productId, newOrder) {
        var orders = JSON.stringify([{id: productId, order: parseInt(newOrder)}]);
        fetch('<?php echo BASE_URL; ?>admin/products.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=update_order&orders=' + encodeURIComponent(orders)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var toast = document.createElement('div');
                toast.style.cssText = 'position:fixed; bottom:30px; right:30px; z-index:9999; background:#fff; color:#16a34a; padding:12px 18px; border-radius:10px; font-size:12px; display:flex; align-items:center; gap:8px; box-shadow:0 6px 20px rgba(0,0,0,0.1); border-left:4px solid #16a34a; font-family:Poppins,sans-serif; animation:slideInRight 0.3s ease;';
                toast.innerHTML = '<i class="fas fa-check-circle"></i> Order updated';
                document.body.appendChild(toast);
                setTimeout(function() { toast.style.opacity = '0'; toast.style.transform = 'translateX(100%)'; toast.style.transition = 'all 0.3s'; setTimeout(function() { toast.remove(); }, 300); }, 2000);
            }
        });
    }

    <?php if (isset($_SESSION['admin_success'])): ?>
    (function() {
        var toast = document.createElement('div');
        toast.style.cssText = 'position:fixed; bottom:30px; right:30px; z-index:9999; background:#fff; color:#16a34a; padding:14px 20px; border-radius:12px; font-size:13px; display:flex; align-items:center; gap:10px; box-shadow:0 8px 30px rgba(0,0,0,0.12); border-left:4px solid #16a34a; animation:slideInRight 0.4s ease, fadeOutRight 0.5s ease 4s forwards; font-family:Poppins,sans-serif;';
        toast.innerHTML = '<i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_success']); ?>';
        document.body.appendChild(toast);
    })();
    <?php unset($_SESSION['admin_success']); endif; ?>

    function updateStock(productId, newStock) {
        fetch('<?php echo BASE_URL; ?>admin/products.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=update_stock&product_id=' + productId + '&new_stock=' + newStock
        })
        .then(function(r) { return r.text(); })
        .then(function() {
            // Show brief success indicator
            var toast = document.createElement('div');
            toast.style.cssText = 'position:fixed; bottom:30px; right:30px; z-index:9999; background:#fff; color:#16a34a; padding:12px 18px; border-radius:10px; font-size:12px; display:flex; align-items:center; gap:8px; box-shadow:0 6px 20px rgba(0,0,0,0.1); border-left:4px solid #16a34a; font-family:Poppins,sans-serif; animation:slideInRight 0.3s ease;';
            toast.innerHTML = '<i class="fas fa-check-circle"></i> Stock updated';
            document.body.appendChild(toast);
            setTimeout(function() { toast.style.opacity = '0'; toast.style.transform = 'translateX(100%)'; toast.style.transition = 'all 0.3s'; setTimeout(function() { toast.remove(); }, 300); }, 2000);
        });
    }
    </script>

    <style>
    @keyframes slideInRight { from { opacity:0; transform:translateX(100%); } to { opacity:1; transform:translateX(0); } }
    @keyframes fadeOutRight { from { opacity:1; transform:translateX(0); } to { opacity:0; transform:translateX(100%); } }
    </style>
</body>
</html>
