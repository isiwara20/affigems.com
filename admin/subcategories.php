<?php
/**
 * Admin Subcategories Management
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/SubcategoryDAL.php';
require_once __DIR__ . '/../dal/CategoryDAL.php';
require_once __DIR__ . '/../helpers/db_schema.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$subcategoryDAL = new SubcategoryDAL();
$categoryDAL = new CategoryDAL();

try {
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    if ($conn) {
        db_repair_auto_increment_table($conn, 'categories');
        db_repair_auto_increment_table($conn, 'subcategories');
    }
} catch (Throwable $e) {
    // Continue; DAL may still work if repair is unnecessary.
}

$categories = array_values(array_filter(
    $categoryDAL->getActive(),
    static fn(array $cat): bool => (int) ($cat['id'] ?? 0) > 0
));

// Ensure sort_order column exists
try {
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    $conn->exec("ALTER TABLE subcategories ADD COLUMN sort_order INT DEFAULT 0 AFTER image");
} catch (Exception $e) {}

// Handle sort order update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    header('Content-Type: application/json');
    $orders = json_decode($_POST['orders'] ?? '[]', true);
    if (!empty($orders)) {
        foreach ($orders as $item) {
            $subcategoryDAL->updateSortOrder((int)$item['id'], (int)$item['order']);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// Ensure upload directory
$uploadDir = __DIR__ . '/../assets/uploads/categories/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$message = '';
$messageType = '';
$editSubcategory = null;

// Check for flash messages
if (isset($_SESSION['subcat_message'])) {
    $message = $_SESSION['subcat_message'];
    $messageType = $_SESSION['subcat_message_type'] ?? 'success';
    unset($_SESSION['subcat_message'], $_SESSION['subcat_message_type']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        
        // Handle image upload
        $imagePath = '';
        $imageUploadAttempted = isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE;
        if ($imageUploadAttempted) {
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => 'Image exceeds server upload limit.',
                    UPLOAD_ERR_FORM_SIZE => 'Image exceeds the 5MB limit.',
                    UPLOAD_ERR_PARTIAL => 'Image upload was incomplete. Please try again.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Server upload folder is missing.',
                    UPLOAD_ERR_CANT_WRITE => 'Server failed to write the uploaded image.',
                    UPLOAD_ERR_EXTENSION => 'Image upload blocked by server extension.',
                ];
                $message = $uploadErrors[$_FILES['image']['error']] ?? 'Image upload failed. Please try again.';
                $messageType = 'error';
            } else {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $fileType = $_FILES['image']['type'];
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo) {
                        $detectedType = finfo_file($finfo, $_FILES['image']['tmp_name']);
                        finfo_close($finfo);
                        if ($detectedType) {
                            $fileType = $detectedType;
                        }
                    }
                }
                $fileSize = $_FILES['image']['size'];

                if (!in_array($fileType, $allowedTypes, true)) {
                    $message = 'Invalid image type. Allowed: JPG, PNG, WEBP';
                    $messageType = 'error';
                } elseif ($fileSize > 5 * 1024 * 1024) {
                    $message = 'Image size must be less than 5MB.';
                    $messageType = 'error';
                } else {
                    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $fileName = 'subcat_' . time() . '_' . uniqid() . '.' . $ext;
                    $destination = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                        require_once __DIR__ . '/../services/ImageOptimizer.php';
                        $optimizedPath = ImageOptimizer::optimizeCategory($destination);
                        $savedFile = ($optimizedPath && file_exists($optimizedPath)) ? $optimizedPath : $destination;
                        if (file_exists($savedFile)) {
                            $imagePath = 'assets/uploads/categories/' . basename($savedFile);
                        } else {
                            $message = 'Failed to process uploaded image.';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Failed to save uploaded image.';
                        $messageType = 'error';
                    }
                }
            }
        }
        
        if (empty($name) && empty($message)) {
            $message = 'Subcategory name is required.';
            $messageType = 'error';
        } elseif ($categoryId === 0 && empty($message)) {
            $message = 'Please select a parent category.';
            $messageType = 'error';
        } elseif (empty($message)) {
            if ($action === 'create') {
                try {
                    if ($subcategoryDAL->create($categoryId, $name, $slug, $description, $imagePath, $status)) {
                        $_SESSION['subcat_message'] = 'Subcategory created successfully!';
                        $_SESSION['subcat_message_type'] = 'success';
                    } else {
                        $_SESSION['subcat_message'] = 'Failed to create subcategory.';
                        $_SESSION['subcat_message_type'] = 'error';
                    }
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate') !== false || (method_exists($e, 'getCode') && $e->getCode() == 23000)) {
                        $_SESSION['subcat_message'] = 'A subcategory with this name already exists. Please use a different name.';
                    } else {
                        $_SESSION['subcat_message'] = 'Failed to create subcategory. Please try again.';
                    }
                    $_SESSION['subcat_message_type'] = 'error';
                }
            } else {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    $message = 'Invalid subcategory. Please try again from the edit link.';
                    $messageType = 'error';
                } elseif ($imageUploadAttempted && empty($imagePath)) {
                    if (empty($message)) {
                        $message = 'Image upload failed. Please use a JPG, PNG, or WEBP file under 5MB.';
                        $messageType = 'error';
                    }
                } else {
                    $old = $subcategoryDAL->getById($id);
                    try {
                        if ($subcategoryDAL->update($id, $categoryId, $name, $slug, $description, $imagePath, $status)) {
                            if (!empty($imagePath) && $old && !empty($old['image']) && $old['image'] !== $imagePath) {
                                $oldFile = __DIR__ . '/../' . $old['image'];
                                if (file_exists($oldFile)) {
                                    unlink($oldFile);
                                }
                            }
                            $_SESSION['subcat_message'] = 'Subcategory updated successfully!';
                            $_SESSION['subcat_message_type'] = 'success';
                        } else {
                            if (!empty($imagePath)) {
                                $newFile = __DIR__ . '/../' . $imagePath;
                                if (file_exists($newFile)) {
                                    unlink($newFile);
                                }
                            }
                            $_SESSION['subcat_message'] = 'Failed to update subcategory.';
                            $_SESSION['subcat_message_type'] = 'error';
                        }
                    } catch (Exception $e) {
                        if (!empty($imagePath)) {
                            $newFile = __DIR__ . '/../' . $imagePath;
                            if (file_exists($newFile)) {
                                unlink($newFile);
                            }
                        }
                        if (strpos($e->getMessage(), 'Duplicate') !== false) {
                            $_SESSION['subcat_message'] = 'A subcategory with this name already exists. Please use a different name.';
                        } else {
                            $_SESSION['subcat_message'] = 'Failed to update subcategory. Please try again.';
                        }
                        $_SESSION['subcat_message_type'] = 'error';
                    }
                }
            }
            if (empty($message)) {
                header('Location: ' . BASE_URL . 'admin/subcategories.php');
                exit;
            }
        }
    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $old = $subcategoryDAL->getById($id);
        if ($old && !empty($old['image'])) {
            $oldFile = __DIR__ . '/../' . $old['image'];
            if (file_exists($oldFile)) unlink($oldFile);
        }
        if ($subcategoryDAL->delete($id)) {
            $_SESSION['subcat_message'] = 'Subcategory deleted successfully!';
            $_SESSION['subcat_message_type'] = 'success';
        } else {
            $_SESSION['subcat_message'] = 'Failed to delete subcategory.';
            $_SESSION['subcat_message_type'] = 'error';
        }
        header('Location: ' . BASE_URL . 'admin/subcategories.php');
        exit;
    }
}

// Handle edit mode
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update' && !empty($message) && !empty($_POST['id'])) {
    $editSubcategory = $subcategoryDAL->getById((int)$_POST['id']);
} elseif (isset($_GET['edit'])) {
    $editSubcategory = $subcategoryDAL->getById((int)$_GET['edit']);
}

// Fetch all subcategories with product counts
$subcategories = $subcategoryDAL->getAll();
$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Subcategories - Admin | ARIGATO.LK</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .dataTables_wrapper .dataTables_filter input { border: 1.5px solid #e4e8f0; border-radius: 8px; padding: 8px 14px; font-size: 13px; outline: none; font-family: 'Poppins', sans-serif; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: #2d6b4f; }
        .dataTables_wrapper .dataTables_length select { border: 1.5px solid #e4e8f0; border-radius: 8px; padding: 6px 10px; font-size: 13px; font-family: 'Poppins', sans-serif; }
        .dataTables_wrapper .dataTables_info { font-size: 12px; color: #5a7a6a; padding-top: 12px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 8px !important; font-size: 12px; padding: 6px 12px !important; margin: 0 2px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #1a3c2a !important; color: #fff !important; border-color: #1a3c2a !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #f0f9f4 !important; color: #0f2b1d !important; }
        .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length { margin-bottom: 16px; font-size: 13px; }
        .dataTables_wrapper { padding: 20px; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php $currentPage = 'subcategories'; include __DIR__ . '/partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php include __DIR__ . '/partials/topbar.php'; ?>

            <!-- Page Content -->
            <div class="dashboard-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Subcategories</h1>
                        <p class="page-subtitle">Manage product subcategories under each category</p>
                    </div>
                    <button class="btn-primary" onclick="resetSubcategoryForm()">
                        <i class="fas fa-plus"></i> Add Subcategory
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Subcategory Form -->
                <div id="subcategoryFormSection" class="form-card" style="<?php echo ($editSubcategory || isset($_GET['add'])) ? 'display:block' : 'display:none'; ?>">
                    <div class="card-header">
                        <h3><?php echo $editSubcategory ? 'Edit Subcategory' : 'Add New Subcategory'; ?></h3>
                        <button class="btn-close" onclick="document.getElementById('subcategoryFormSection').style.display='none'">&times;</button>
                    </div>
                    <form method="POST" id="subcategoryForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?php echo $editSubcategory ? 'update' : 'create'; ?>">
                        <?php if ($editSubcategory): ?>
                        <input type="hidden" name="id" value="<?php echo $editSubcategory['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="category_id">Parent Category <span class="required">*</span></label>
                                <select id="category_id" name="category_id" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($editSubcategory && $editSubcategory['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="name">Subcategory Name <span class="required">*</span></label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($editSubcategory['name'] ?? ''); ?>" 
                                       placeholder="e.g. Laptops, Keyboards, Routers"
                                       required oninput="generateSlug(this.value)">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="slug_preview">Slug</label>
                            <input type="text" id="slug_preview" class="form-control" readonly 
                                   value="<?php echo htmlspecialchars($editSubcategory['slug'] ?? ''); ?>"
                                   style="background:#f0f0f0; color:#666;">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3" placeholder="Optional description"><?php echo htmlspecialchars($editSubcategory['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="subcat_image">Subcategory Image</label>
                                <input type="file" id="subcat_image" name="image" class="form-control-file" accept="image/jpeg,image/png,image/webp">
                                <small class="form-help">Accepted: JPG, PNG, WEBP. Max 5MB. Image will be auto-optimized.</small>
                                <?php if ($editSubcategory && !empty($editSubcategory['image'])): ?>
                                <div class="current-image" style="margin-top:10px;">
                                    <?php
                                    $editImagePath = __DIR__ . '/../' . $editSubcategory['image'];
                                    $editImageVersion = file_exists($editImagePath) ? filemtime($editImagePath) : time();
                                    ?>
                                    <img src="<?php echo htmlspecialchars(media_image_url($editSubcategory['image'])) . '?v=' . $editImageVersion; ?>" alt="" style="max-width:100px; border-radius:8px;">
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="active" <?php echo ($editSubcategory && $editSubcategory['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($editSubcategory && $editSubcategory['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> <?php echo $editSubcategory ? 'Update Subcategory' : 'Save Subcategory'; ?>
                            </button>
                            <button type="button" class="btn-secondary" onclick="document.getElementById('subcategoryFormSection').style.display='none'">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Subcategories Table -->
                <div class="table-card">
                    <table class="data-table" id="subcategoriesTable">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Image</th>
                                <th>Subcategory</th>
                                <th>Parent Category</th>
                                <th>Order</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="subcategorySortable">
                            <?php if (empty($subcategories)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No subcategories found. Add your first subcategory!</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($subcategories as $sub): ?>
                            <tr data-id="<?php echo $sub['id']; ?>">
                                <td style="cursor:grab; color:#bbb;"><i class="fas fa-grip-vertical"></i></td>
                                <td>
                                    <?php if (!empty($sub['image'])): ?>
                                    <?php
                                    $subImagePath = __DIR__ . '/../' . $sub['image'];
                                    $subImageVersion = file_exists($subImagePath) ? filemtime($subImagePath) : time();
                                    ?>
                                    <img src="<?php echo htmlspecialchars(media_image_url($sub['image'])) . '?v=' . $subImageVersion; ?>" alt="" class="table-img">
                                    <?php else: ?>
                                    <div class="table-img-placeholder"><i class="fas fa-sitemap"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($sub['name']); ?></strong></td>
                                <td>
                                    <span class="badge-count" style="background:#f0f9f4; color:#1a3c2a;"><?php echo htmlspecialchars($sub['category_name']); ?></span>
                                </td>
                                <td>
                                    <input type="number" value="<?php echo (int)($sub['sort_order'] ?? 0); ?>" min="0" style="width:60px; padding:5px 8px; border:1.5px solid #e4e8f0; border-radius:6px; font-size:12px; text-align:center;" onchange="updateSingleOrder(<?php echo $sub['id']; ?>, this.value)">
                                </td>
                                <td>
                                    <span class="badge-count"><?php echo $subcategoryDAL->getProductCount($sub['id']); ?></span>
                                </td>
                                <td>
                                    <span class="status <?php echo $sub['status']; ?>">
                                        <?php echo ucfirst($sub['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="?edit=<?php echo $sub['id']; ?>" class="btn-action edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this subcategory? Products will be unlinked.')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
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

    // Auto-generate slug
    function generateSlug(value) {
        var slug = value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
        document.getElementById('slug_preview').value = slug;
    }

    // Reset form to "Add New" mode
    function resetSubcategoryForm() {
        if (window.location.search.indexOf('edit=') !== -1) {
            window.location.href = '<?php echo BASE_URL; ?>admin/subcategories.php?add=1';
            return;
        }
        var form = document.getElementById('subcategoryForm');
        form.reset();
        form.querySelector('input[name="action"]').value = 'create';
        var idField = form.querySelector('input[name="id"]');
        if (idField) idField.remove();
        document.getElementById('name').value = '';
        document.getElementById('slug_preview').value = '';
        document.getElementById('description').value = '';
        document.getElementById('status').value = 'active';
        document.getElementById('category_id').value = '';
        var currentImg = form.querySelector('.current-image');
        if (currentImg) currentImg.style.display = 'none';
        document.querySelector('#subcategoryFormSection .card-header h3').textContent = 'Add New Subcategory';
        document.getElementById('subcategoryFormSection').style.display = 'block';
        document.getElementById('subcategoryFormSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Initialize DataTable
    $(document).ready(function() {
        if ($('#subcategoriesTable tbody tr').length > 1 || ($('#subcategoriesTable tbody tr').length === 1 && !$('#subcategoriesTable tbody tr td').hasClass('text-center'))) {
            $('#subcategoriesTable').DataTable({
                order: [[4, 'asc']],
                pageLength: 10,
                columnDefs: [
                    { orderable: false, targets: [0, 7] }
                ],
                language: {
                    search: "Search subcategories:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ subcategories"
                }
            });
        }
    });

    function updateSingleOrder(subcatId, newOrder) {
        var orders = JSON.stringify([{id: subcatId, order: parseInt(newOrder)}]);
        fetch('<?php echo BASE_URL; ?>admin/subcategories.php', {
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
    </script>
</body>
</html>
