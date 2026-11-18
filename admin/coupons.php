<?php
/**
 * Admin Coupons Management
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/CouponDAL.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$couponDAL = new CouponDAL();
$message = '';
$messageType = '';
$editCoupon = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $code = trim($_POST['code'] ?? '');
        $discountType = $_POST['discount_type'] ?? 'percentage';
        $discountValue = (float)($_POST['discount_value'] ?? 0);
        $minOrder = (float)($_POST['min_order'] ?? 0);
        $maxUses = (int)($_POST['max_uses'] ?? 0);
        $expiresAt = $_POST['expires_at'] ?? '';
        $status = $_POST['status'] ?? 'active';

        if (empty($code)) {
            $message = 'Coupon code is required.';
            $messageType = 'error';
        } elseif ($discountValue <= 0) {
            $message = 'Discount value must be greater than zero.';
            $messageType = 'error';
        } else {
            if ($action === 'create') {
                if ($couponDAL->create($code, $discountType, $discountValue, $minOrder, $maxUses, $expiresAt, $status)) {
                    $message = 'Coupon created successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to create coupon. Code may already exist.';
                    $messageType = 'error';
                }
            } else {
                $id = (int)$_POST['id'];
                if ($couponDAL->update($id, $code, $discountType, $discountValue, $minOrder, $maxUses, $expiresAt, $status)) {
                    $message = 'Coupon updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update coupon.';
                    $messageType = 'error';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($couponDAL->delete($id)) {
            $message = 'Coupon deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete coupon.';
            $messageType = 'error';
        }
    }
}

// Handle edit mode
if (isset($_GET['edit'])) {
    $editCoupon = $couponDAL->getById((int)$_GET['edit']);
}

$coupons = $couponDAL->getAll();
$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Coupons - Admin | ARIGATO.LK</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <?php $currentPage = 'coupons'; include __DIR__ . '/partials/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/partials/topbar.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Coupons</h1>
                        <p class="page-subtitle">Manage discount coupons</p>
                    </div>
                    <button class="btn-primary" onclick="document.getElementById('couponFormSection').style.display='block'">
                        <i class="fas fa-plus"></i> Create Coupon
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Coupon Form -->
                <div id="couponFormSection" class="form-card" style="<?php echo ($editCoupon) ? 'display:block' : 'display:none'; ?>">
                    <div class="card-header">
                        <h3><?php echo $editCoupon ? 'Edit Coupon' : 'Create New Coupon'; ?></h3>
                        <button class="btn-close" onclick="document.getElementById('couponFormSection').style.display='none'">&times;</button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $editCoupon ? 'update' : 'create'; ?>">
                        <?php if ($editCoupon): ?>
                        <input type="hidden" name="id" value="<?php echo $editCoupon['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Coupon Code <span class="required">*</span></label>
                                <input type="text" name="code" class="form-control" style="text-transform:uppercase;" 
                                       value="<?php echo htmlspecialchars($editCoupon['code'] ?? ''); ?>" required placeholder="e.g. SAVE20">
                            </div>
                            <div class="form-group">
                                <label>Discount Type</label>
                                <select name="discount_type" class="form-control">
                                    <option value="percentage" <?php echo (isset($editCoupon['discount_type']) && $editCoupon['discount_type'] === 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                                    <option value="fixed" <?php echo (isset($editCoupon['discount_type']) && $editCoupon['discount_type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount (LKR)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Discount Value <span class="required">*</span></label>
                                <input type="number" name="discount_value" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($editCoupon['discount_value'] ?? ''); ?>" required placeholder="e.g. 10">
                            </div>
                            <div class="form-group">
                                <label>Minimum Order (LKR)</label>
                                <input type="number" name="min_order" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($editCoupon['min_order'] ?? '0'); ?>" placeholder="0 = no minimum">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Max Uses</label>
                                <input type="number" name="max_uses" class="form-control" min="0"
                                       value="<?php echo htmlspecialchars($editCoupon['max_uses'] ?? '0'); ?>" placeholder="0 = unlimited">
                                <small class="form-help">0 means unlimited usage</small>
                            </div>
                            <div class="form-group">
                                <label>Expires On</label>
                                <input type="date" name="expires_at" class="form-control"
                                       value="<?php echo htmlspecialchars($editCoupon['expires_at'] ?? ''); ?>">
                                <small class="form-help">Leave empty for no expiry</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control" style="max-width:200px;">
                                <option value="active" <?php echo (isset($editCoupon['status']) && $editCoupon['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($editCoupon['status']) && $editCoupon['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> <?php echo $editCoupon ? 'Update Coupon' : 'Create Coupon'; ?>
                            </button>
                            <button type="button" class="btn-secondary" onclick="document.getElementById('couponFormSection').style.display='none'">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Coupons Table -->
                <div class="table-card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Discount</th>
                                <th>Min Order</th>
                                <th>Usage</th>
                                <th>Expires</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coupons)): ?>
                            <tr><td colspan="7" class="text-center">No coupons created yet.</td></tr>
                            <?php else: ?>
                            <?php foreach ($coupons as $coupon): ?>
                            <tr>
                                <td><code style="font-size:13px; font-weight:700; background:#f0f2f8; padding:5px 12px; border-radius:6px;"><?php echo htmlspecialchars($coupon['code']); ?></code></td>
                                <td>
                                    <strong><?php echo $coupon['discount_type'] === 'percentage' ? $coupon['discount_value'] . '%' : 'LKR ' . number_format($coupon['discount_value'], 0); ?></strong>
                                    <br><small style="color:#888;"><?php echo $coupon['discount_type'] === 'percentage' ? 'Percentage' : 'Fixed'; ?></small>
                                </td>
                                <td>LKR <?php echo number_format($coupon['min_order'], 0); ?></td>
                                <td>
                                    <?php echo $coupon['used_count']; ?> / <?php echo $coupon['max_uses'] > 0 ? $coupon['max_uses'] : '∞'; ?>
                                </td>
                                <td>
                                    <?php if ($coupon['expires_at']): ?>
                                        <?php echo date('M d, Y', strtotime($coupon['expires_at'])); ?>
                                        <?php if (strtotime($coupon['expires_at']) < time()): ?>
                                        <br><small style="color:#ef4444;">Expired</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:#888;">No expiry</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status <?php echo $coupon['status']; ?>"><?php echo ucfirst($coupon['status']); ?></span></td>
                                <td class="actions">
                                    <a href="?edit=<?php echo $coupon['id']; ?>" class="btn-action edit"><i class="fas fa-edit"></i></a>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this coupon?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $coupon['id']; ?>">
                                        <button type="submit" class="btn-action delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="admin-footer">
                    <p>&copy; 2026 ARIGATO.LK. All rights reserved.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.querySelector('.admin-layout').classList.toggle('sidebar-collapsed');
    });
    </script>
</body>
</html>
