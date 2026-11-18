<?php
/**
 * Admin Categories Management
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/db_schema.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Initialize database and create table if not exists
$database = new Database();
$conn = $database->getConnection();

$conn->exec("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

db_repair_auto_increment_table($conn, 'categories');

// Add sort_order column if not exists
try {
    $conn->exec("ALTER TABLE categories ADD COLUMN sort_order INT DEFAULT 0 AFTER image");
} catch (Exception $e) {}

// Handle sort order update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    header('Content-Type: application/json');
    $orders = json_decode($_POST['orders'] ?? '[]', true);
    if (!empty($orders)) {
        foreach ($orders as $item) {
            $stmt = $conn->prepare("UPDATE categories SET sort_order = :order WHERE id = :id");
            $stmt->execute([':order' => (int)$item['order'], ':id' => (int)$item['id']]);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// Ensure upload directory exists
$uploadDir = __DIR__ . '/../assets/uploads/categories/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$message = '';
$messageType = '';
$editCategory = null;

// Check for flash messages from redirect
if (isset($_SESSION['cat_message'])) {
    $message = $_SESSION['cat_message'];
    $messageType = $_SESSION['cat_message_type'] ?? 'success';
    unset($_SESSION['cat_message'], $_SESSION['cat_message_type']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        
        // Handle image upload
        $imagePath = '';
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
                $fileName = 'category_' . time() . '_' . uniqid() . '.' . $ext;
                $destination = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    // Optimize category image
                    require_once __DIR__ . '/../services/ImageOptimizer.php';
                    $optimizedPath = ImageOptimizer::optimizeCategory($destination);
                    $imagePath = 'assets/uploads/categories/' . basename($optimizedPath);
                }
            }
        }
        
        if (empty($name) && empty($message)) {
            $message = 'Category name is required.';
            $messageType = 'error';
        } elseif (empty($message)) {
            if ($action === 'create') {
                try {
                    $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, image, status) VALUES (:name, :slug, :description, :image, :status)");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':slug', $slug);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':image', $imagePath);
                    $stmt->bindParam(':status', $status);
                    
                    if ($stmt->execute()) {
                        $_SESSION['cat_message'] = 'Category created successfully!';
                        $_SESSION['cat_message_type'] = 'success';
                        header('Location: ' . BASE_URL . 'admin/categories.php');
                        exit;
                    } else {
                        $message = 'Failed to create category.';
                        $messageType = 'error';
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $message = 'A category with this name already exists. Please use a different name.';
                    } else {
                        $message = 'Failed to create category. Please try again.';
                    }
                    $messageType = 'error';
                }
            } elseif ($action === 'update') {
                $id = (int)$_POST['id'];
                
                // If no new image uploaded, keep old one
                if (empty($imagePath)) {
                    $stmt = $conn->prepare("UPDATE categories SET name = :name, slug = :slug, description = :description, status = :status WHERE id = :id");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':slug', $slug);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                } else {
                    // Delete old image
                    $oldStmt = $conn->prepare("SELECT image FROM categories WHERE id = :id");
                    $oldStmt->execute([':id' => $id]);
                    $oldCat = $oldStmt->fetch();
                    if ($oldCat && $oldCat['image']) {
                        $oldFile = __DIR__ . '/../' . $oldCat['image'];
                        if (file_exists($oldFile)) unlink($oldFile);
                    }
                    
                    $stmt = $conn->prepare("UPDATE categories SET name = :name, slug = :slug, description = :description, image = :image, status = :status WHERE id = :id");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':slug', $slug);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':image', $imagePath);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                }
                
                if ($stmt->execute()) {
                    $message = 'Category updated successfully!';
                    $messageType = 'success';
                    // Redirect to clear edit mode from URL
                    $_SESSION['cat_message'] = $message;
                    $_SESSION['cat_message_type'] = $messageType;
                    header('Location: ' . BASE_URL . 'admin/categories.php');
                    exit;
                } else {
                    $message = 'Failed to update category.';
                    $messageType = 'error';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Delete category image file
        $stmt = $conn->prepare("SELECT image FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $cat = $stmt->fetch();
        if ($cat && $cat['image']) {
            $oldFile = __DIR__ . '/../' . $cat['image'];
            if (file_exists($oldFile)) unlink($oldFile);
        }

        // Delete subcategory images
        $subStmt = $conn->prepare("SELECT image FROM subcategories WHERE category_id = :id");
        $subStmt->execute([':id' => $id]);
        $subs = $subStmt->fetchAll();
        foreach ($subs as $sub) {
            if (!empty($sub['image'])) {
                $subFile = __DIR__ . '/../' . $sub['image'];
                if (file_exists($subFile)) unlink($subFile);
            }
        }

        // Delete product images
        $prodStmt = $conn->prepare("SELECT image, images FROM products WHERE category_id = :id");
        $prodStmt->execute([':id' => $id]);
        $prods = $prodStmt->fetchAll();
        foreach ($prods as $prod) {
            if (!empty($prod['image'])) {
                $pFile = __DIR__ . '/../' . $prod['image'];
                if (file_exists($pFile)) unlink($pFile);
            }
            if (!empty($prod['images'])) {
                $gallery = json_decode($prod['images'], true) ?: [];
                foreach ($gallery as $gImg) {
                    $gFile = __DIR__ . '/../' . $gImg;
                    if (file_exists($gFile)) unlink($gFile);
                }
            }
        }

        // Delete subcategories (CASCADE will handle products)
        $conn->prepare("DELETE FROM subcategories WHERE category_id = :id")->execute([':id' => $id]);
        
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $_SESSION['cat_message'] = 'Category deleted successfully!';
            $_SESSION['cat_message_type'] = 'success';
            header('Location: ' . BASE_URL . 'admin/categories.php');
            exit;
        } else {
            $message = 'Failed to delete category.';
            $messageType = 'error';
        }
    }
}

// Handle edit mode
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $editId, PDO::PARAM_INT);
    $stmt->execute();
    $editCategory = $stmt->fetch();
}

// Fetch all categories with product count
$stmt = $conn->prepare("SELECT c.*, (SELECT COUNT(*) FROM subcategories WHERE category_id = c.id) as subcat_count FROM categories c ORDER BY c.sort_order ASC, c.name ASC");
$stmt->execute();
$categories = $stmt->fetchAll();

$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Categories - Admin | ARIGATO.LK</title>
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
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php $currentPage = 'categories'; include __DIR__ . '/partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php include __DIR__ . '/partials/topbar.php'; ?>

            <!-- Page Content -->
            <div class="dashboard-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Categories</h1>
                        <p class="page-subtitle">Manage product categories</p>
                    </div>
                    <button class="btn-primary" onclick="resetCategoryForm()">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Category Form -->
                <div id="categoryFormSection" class="form-card" style="<?php echo ($editCategory || isset($_GET['add'])) ? 'display:block' : 'display:none'; ?>">
                    <div class="card-header">
                        <h3><?php echo $editCategory ? 'Edit Category' : 'Add New Category'; ?></h3>
                        <button class="btn-close" onclick="document.getElementById('categoryFormSection').style.display='none'">&times;</button>
                    </div>
                    <form method="POST" id="categoryForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?php echo $editCategory ? 'update' : 'create'; ?>">
                        <?php if ($editCategory): ?>
                        <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="name">Category Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($editCategory['name'] ?? ''); ?>" 
                                   required oninput="generateSlug(this.value)">
                        </div>

                        <div class="form-group">
                            <label for="slug_preview">Slug</label>
                            <input type="text" id="slug_preview" class="form-control" readonly 
                                   value="<?php echo htmlspecialchars($editCategory['slug'] ?? ''); ?>"
                                   style="background:#f0f0f0; color:#666;">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($editCategory['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="cat_image">Category Image</label>
                            <input type="file" id="cat_image" name="image" class="form-control-file" accept="image/jpeg,image/png,image/webp">
                            <small class="form-help">Accepted: JPG, PNG, WEBP. Max 5MB.</small>
                            <?php if ($editCategory && $editCategory['image']): ?>
                            <div class="current-image" style="margin-top:10px;">
                                <img src="<?php echo htmlspecialchars(media_image_url($editCategory['image'])); ?>" alt="" style="max-width:100px; border-radius:8px;">
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="active" <?php echo (isset($editCategory['status']) && $editCategory['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($editCategory['status']) && $editCategory['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> <?php echo $editCategory ? 'Update Category' : 'Save Category'; ?>
                            </button>
                            <button type="button" class="btn-secondary" onclick="document.getElementById('categoryFormSection').style.display='none'">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Categories Table -->
                <div class="table-card">
                    <table class="data-table" id="categoriesTable">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Order</th>
                                <th>Subcategories</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categorySortable">
                            <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No categories found. Add your first category!</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($categories as $idx => $cat): ?>
                            <tr data-id="<?php echo $cat['id']; ?>">
                                <td style="cursor:grab; color:#bbb;"><i class="fas fa-grip-vertical"></i></td>
                                <td>
                                    <?php if ($cat['image']): ?>
                                    <img src="<?php echo htmlspecialchars(media_image_url($cat['image'])); ?>" alt="" class="table-img">
                                    <?php else: ?>
                                    <div class="table-img-placeholder"><i class="fas fa-folder"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                                <td>
                                    <input type="number" value="<?php echo (int)($cat['sort_order'] ?? 0); ?>" min="0" style="width:60px; padding:5px 8px; border:1.5px solid #e4e8f0; border-radius:6px; font-size:12px; text-align:center;" onchange="updateSingleOrder(<?php echo $cat['id']; ?>, this.value)">
                                </td>
                                <td>
                                    <span class="badge-count"><?php echo (int)$cat['subcat_count']; ?></span>
                                </td>
                                <td>
                                    <span class="status <?php echo $cat['status']; ?>">
                                        <?php echo ucfirst($cat['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="?edit=<?php echo $cat['id']; ?>" class="btn-action edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display:inline" id="deleteForm_<?php echo $cat['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                        <button type="button" class="btn-action delete" title="Delete" onclick="showDeleteConfirm(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars(addslashes($cat['name'])); ?>')">
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
                    <p>&copy; 2026 ARIGATO.LK. All rights reserved.</p>
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

    // Auto-generate slug from name
    function generateSlug(value) {
        var slug = value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
        document.getElementById('slug_preview').value = slug;
    }

    // Reset form to "Add New" mode
    function resetCategoryForm() {
        // If we're on an edit URL, redirect to clean page with add flag
        if (window.location.search.indexOf('edit=') !== -1) {
            window.location.href = '<?php echo BASE_URL; ?>admin/categories.php?add=1';
            return;
        }
        // Otherwise just clear and show the form
        var form = document.getElementById('categoryForm');
        form.reset();
        form.querySelector('input[name="action"]').value = 'create';
        var idField = form.querySelector('input[name="id"]');
        if (idField) idField.remove();
        document.getElementById('name').value = '';
        document.getElementById('slug_preview').value = '';
        document.getElementById('description').value = '';
        document.getElementById('status').value = 'active';
        var currentImg = form.querySelector('.current-image');
        if (currentImg) currentImg.style.display = 'none';
        // Update title and button
        form.closest('.form-card').querySelector('.card-header h3').textContent = 'Add New Category';
        form.querySelector('.btn-primary').innerHTML = '<i class="fas fa-save"></i> Save Category';
        document.getElementById('categoryFormSection').style.display = 'block';
    }

    $(document).ready(function() {
        $('#categoriesTable').DataTable({
            pageLength: 10,
            ordering: true,
            columnDefs: [
                { orderable: false, targets: [0, 4, 7] }
            ],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ categories",
                paginate: { previous: "‹", next: "›" }
            }
        });
    });

    // Update single category sort order
    function updateSingleOrder(catId, newOrder) {
        var orders = JSON.stringify([{id: catId, order: parseInt(newOrder)}]);
        fetch('<?php echo BASE_URL; ?>admin/categories.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=update_order&orders=' + encodeURIComponent(orders)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var toast = document.createElement('div');
                toast.className = 'alert alert-success';
                toast.style.cssText = 'position:fixed; bottom:30px; right:30px; z-index:9999; max-width:300px; box-shadow:0 8px 30px rgba(0,0,0,0.12); animation: slideInRight 0.4s ease, fadeOut 0.5s ease 2s forwards;';
                toast.innerHTML = '<i class="fas fa-check-circle"></i> Order updated!';
                document.body.appendChild(toast);
                setTimeout(function() { toast.remove(); }, 3000);
            }
        });
    }

    // Delete confirmation overlay
    var deleteCatId = null;
    function showDeleteConfirm(catId, catName) {
        deleteCatId = catId;
        document.getElementById('deleteCatName').textContent = catName;
        document.getElementById('deleteCatOverlay').classList.add('show');
    }
    function hideDeleteConfirm() {
        document.getElementById('deleteCatOverlay').classList.remove('show');
        deleteCatId = null;
    }
    function confirmDeleteCategory() {
        if (deleteCatId) {
            document.getElementById('deleteForm_' + deleteCatId).submit();
        }
    }
    // Close on overlay click
    document.getElementById('deleteCatOverlay').addEventListener('click', function(e) {
        if (e.target === this) hideDeleteConfirm();
    });
    </script>

    <!-- Delete Confirmation Overlay -->
    <div class="delete-cat-overlay" id="deleteCatOverlay">
        <div class="delete-cat-modal">
            <div class="delete-cat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Delete Category</h3>
            <p>Are you sure you want to delete <strong id="deleteCatName"></strong>?</p>
            <p class="delete-warning">This will permanently delete all subcategories and products under this category. This action cannot be undone.</p>
            <div class="delete-cat-actions">
                <button class="btn-cancel-del" onclick="hideDeleteConfirm()">Cancel</button>
                <button class="btn-confirm-del" onclick="confirmDeleteCategory()"><i class="fas fa-trash"></i> Yes, Delete</button>
            </div>
        </div>
    </div>

    <style>
    .delete-cat-overlay { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center; backdrop-filter:blur(3px); }
    .delete-cat-overlay.show { display:flex; animation:fadeInOverlay 0.2s ease; }
    .delete-cat-modal { background:#fff; border-radius:18px; padding:40px; max-width:400px; width:90%; text-align:center; animation:scaleIn 0.25s ease; box-shadow:0 20px 50px rgba(0,0,0,0.15); }
    .delete-cat-icon { width:64px; height:64px; background:#fef2f2; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 18px; font-size:26px; color:#dc2626; }
    .delete-cat-modal h3 { font-size:18px; font-weight:700; color:#0f2b1d; margin-bottom:10px; }
    .delete-cat-modal p { font-size:13px; color:#555; line-height:1.6; margin-bottom:8px; }
    .delete-warning { background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:12px 16px; font-size:12px; color:#dc2626; margin-bottom:20px !important; }
    .delete-cat-actions { display:flex; gap:12px; justify-content:center; }
    .btn-cancel-del { padding:11px 24px; background:#f0f2f8; color:#333; border:none; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; font-family:'Poppins',sans-serif; transition:background 0.2s; }
    .btn-cancel-del:hover { background:#e4e8f0; }
    .btn-confirm-del { padding:11px 24px; background:#dc2626; color:#fff; border:none; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; font-family:'Poppins',sans-serif; display:inline-flex; align-items:center; gap:6px; transition:background 0.2s; }
    .btn-confirm-del:hover { background:#b91c1c; }
    @keyframes fadeInOverlay { from { opacity:0; } to { opacity:1; } }
    @keyframes scaleIn { from { transform:scale(0.9); opacity:0; } to { transform:scale(1); opacity:1; } }
    </style>
</body>
</html>
