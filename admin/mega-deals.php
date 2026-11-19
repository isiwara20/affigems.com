<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/MegaDealDAL.php';
require_once __DIR__ . '/../dal/ProductDAL.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$megaDealDAL = new MegaDealDAL();
$productDAL = new ProductDAL();

// Get promo popup settings
require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
$settingsDAL = new SiteSettingsDAL();
$promoImage = $settingsDAL->get('promo_popup_image') ?? '';
$promoActive = $settingsDAL->get('promo_popup_active') ?? '0';
$megaDealsEnabled = $settingsDAL->get('mega_deals_enabled', '1') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_mega_settings') {
        $megaDealsEnabledVal = isset($_POST['mega_deals_enabled']) ? '1' : '0';
        $settingsDAL->set('mega_deals_enabled', $megaDealsEnabledVal);
        $_SESSION['mega_msg'] = 'Hot deals settings updated!';
        $_SESSION['mega_type'] = 'success';
    } elseif ($action === 'add') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $dealPrice = (float)($_POST['deal_price'] ?? 0);
        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;
        
        if ($productId && $dealPrice > 0) {
            if ($megaDealDAL->addDeal($productId, $dealPrice, $startDate ?: null, $endDate ?: null)) {
                $_SESSION['mega_msg'] = 'Mega deal added successfully!';
                $_SESSION['mega_type'] = 'success';
            } else {
                $_SESSION['mega_msg'] = 'Failed to add deal.';
                $_SESSION['mega_type'] = 'error';
            }
        } else {
            $_SESSION['mega_msg'] = 'Please select a product and enter a valid deal price.';
            $_SESSION['mega_type'] = 'error';
        }
    } elseif ($action === 'remove') {
        $dealId = (int)($_POST['deal_id'] ?? 0);
        if ($megaDealDAL->removeDeal($dealId)) {
            $_SESSION['mega_msg'] = 'Deal removed and product price restored.';
            $_SESSION['mega_type'] = 'success';
        }
    } elseif ($action === 'toggle') {
        $dealId = (int)($_POST['deal_id'] ?? 0);
        if ($megaDealDAL->toggleStatus($dealId)) {
            $_SESSION['mega_msg'] = 'Deal status updated.';
            $_SESSION['mega_type'] = 'success';
        }
    } elseif ($action === 'update_promo') {
        $promoActiveVal = isset($_POST['promo_active']) ? '1' : '0';
        $settingsDAL->set('promo_popup_active', $promoActiveVal);
        
        // Handle image upload with optimization
        if (!empty($_FILES['promo_image']['name'])) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $ext = strtolower(pathinfo($_FILES['promo_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (in_array($ext, $allowed) && $_FILES['promo_image']['size'] <= 5 * 1024 * 1024) {
                $filename = 'promo_popup_' . time() . '.webp';
                $filepath = $uploadDir . $filename;
                $tmpFile = $_FILES['promo_image']['tmp_name'];
                
                // Optimize: resize and convert to WebP
                $optimized = false;
                if (function_exists('imagecreatefromjpeg')) {
                    $sourceImage = null;
                    switch ($ext) {
                        case 'jpg': case 'jpeg':
                            $sourceImage = @imagecreatefromjpeg($tmpFile);
                            break;
                        case 'png':
                            $sourceImage = @imagecreatefrompng($tmpFile);
                            break;
                        case 'webp':
                            $sourceImage = @imagecreatefromwebp($tmpFile);
                            break;
                        case 'gif':
                            $sourceImage = @imagecreatefromgif($tmpFile);
                            break;
                    }
                    
                    if ($sourceImage) {
                        $origWidth = imagesx($sourceImage);
                        $origHeight = imagesy($sourceImage);
                        
                        // Resize if larger than 1200px width
                        $maxWidth = 1200;
                        if ($origWidth > $maxWidth) {
                            $newWidth = $maxWidth;
                            $newHeight = (int)($origHeight * ($maxWidth / $origWidth));
                            $resized = imagecreatetruecolor($newWidth, $newHeight);
                            imagealphablending($resized, false);
                            imagesavealpha($resized, true);
                            imagecopyresampled($resized, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                            imagedestroy($sourceImage);
                            $sourceImage = $resized;
                        }
                        
                        // Save as WebP with 80% quality
                        if (function_exists('imagewebp')) {
                            $optimized = imagewebp($sourceImage, $filepath, 80);
                        }
                        imagedestroy($sourceImage);
                    }
                }
                
                // Fallback: just move file if optimization failed
                if (!$optimized) {
                    $filename = 'promo_popup_' . time() . '.' . $ext;
                    $filepath = $uploadDir . $filename;
                    move_uploaded_file($tmpFile, $filepath);
                }
                
                // Delete old image
                $oldImage = $settingsDAL->get('promo_popup_image');
                if ($oldImage && file_exists(__DIR__ . '/../' . $oldImage)) {
                    unlink(__DIR__ . '/../' . $oldImage);
                }
                $settingsDAL->set('promo_popup_image', 'assets/uploads/' . $filename);
                $_SESSION['mega_msg'] = 'Promo popup updated! Image optimized for fast loading.';
                $_SESSION['mega_type'] = 'success';
            } else {
                $_SESSION['mega_msg'] = 'Invalid image. Use JPG/PNG/WEBP under 5MB.';
                $_SESSION['mega_type'] = 'error';
            }
        } else {
            $_SESSION['mega_msg'] = 'Promo popup settings updated!';
            $_SESSION['mega_type'] = 'success';
        }
    } elseif ($action === 'remove_promo') {
        $oldImage = $settingsDAL->get('promo_popup_image');
        if ($oldImage && file_exists(__DIR__ . '/../' . $oldImage)) {
            unlink(__DIR__ . '/../' . $oldImage);
        }
        $settingsDAL->set('promo_popup_image', '');
        $settingsDAL->set('promo_popup_active', '0');
        $_SESSION['mega_msg'] = 'Promo popup removed.';
        $_SESSION['mega_type'] = 'success';
    }
    
    // Redirect to prevent resubmission
    header('Location: ' . BASE_URL . 'admin/mega-deals.php');
    exit;
}

// Get flash messages
$message = $_SESSION['mega_msg'] ?? '';
$messageType = $_SESSION['mega_type'] ?? '';
unset($_SESSION['mega_msg'], $_SESSION['mega_type']);

$deals = $megaDealDAL->getAllDeals();
$products = $productDAL->getActive();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Monthly Mega Deals - Admin | <?php echo ADMIN_BRAND; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <?php $currentPage = 'mega-deals'; include __DIR__ . '/partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php include __DIR__ . '/partials/topbar.php'; ?>

            <!-- Page Content -->
            <div class="dashboard-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title"><i class="fas fa-fire" style="color:#ef4444;"></i> Monthly Mega Deals</h1>
                        <p class="page-subtitle">Select products and set special discounted prices</p>
                    </div>
                    <button class="btn-primary" onclick="document.getElementById('dealFormSection').style.display='block'">
                        <i class="fas fa-plus"></i> Add Deal
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Hot Deals Section Toggle -->
                <div class="table-card" style="margin-bottom:24px;">
                    <div style="padding:24px;">
                        <h3 style="font-size:15px; font-weight:700; color:#1a1a2e; margin-bottom:6px; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-fire" style="color:#ef4444;"></i> Hot Deals on Storefront
                        </h3>
                        <p style="font-size:12px; color:#888; margin-bottom:18px;">When enabled, the Monthly Mega Deals section appears on the home page and a Hot Deals category is shown in the shop sidebar.</p>
                        <form method="POST" style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                            <input type="hidden" name="action" value="update_mega_settings">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:500; color:#333;">
                                <input type="checkbox" name="mega_deals_enabled" value="1" <?php echo $megaDealsEnabled ? 'checked' : ''; ?> style="width:16px; height:16px; accent-color:#ef4444;">
                                Enable Hot Deals section
                            </label>
                            <button type="submit" class="btn-primary" style="padding:8px 18px;"><i class="fas fa-save"></i> Save</button>
                        </form>
                    </div>
                </div>

                <!-- Add Deal Form -->
                <div id="dealFormSection" class="form-card" style="display:none;">
                    <div class="card-header">
                        <h3>Add Product to Mega Deals</h3>
                        <button class="btn-close" onclick="document.getElementById('dealFormSection').style.display='none'">&times;</button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <label for="product_id">Select Product <span class="required">*</span></label>
                            <select name="product_id" id="product_id" class="form-control" required>
                                <option value="">-- Choose a Product --</option>
                                <?php foreach ($products as $p): ?>
                                <option value="<?php echo $p['id']; ?>">
                                    <?php echo htmlspecialchars($p['name']); ?> (LKR <?php echo number_format($p['price'], 0); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label for="deal_price">Deal Price (LKR) <span class="required">*</span></label>
                                <input type="number" id="deal_price" name="deal_price" class="form-control" required step="0.01" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" name="start_date" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" name="end_date" class="form-control">
                            </div>
                        </div>

                        <small class="form-help" style="display:block; margin-bottom:16px;">Leave dates empty for unlimited duration. The product's price will be updated to the deal price automatically.</small>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary"><i class="fas fa-plus"></i> Add Mega Deal</button>
                            <button type="button" class="btn-secondary" onclick="document.getElementById('dealFormSection').style.display='none'">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Deals Table -->
                <div class="table-card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Original Price</th>
                                <th>Deal Price</th>
                                <th>Discount</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deals)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No mega deals yet. Click "Add Deal" to create one!</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($deals as $deal): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <?php if ($deal['image']): ?>
                                        <img src="<?php echo htmlspecialchars(media_image_url($deal['image'])); ?>" alt="" class="table-img">
                                        <?php else: ?>
                                        <div class="table-img-placeholder"><i class="fas fa-box"></i></div>
                                        <?php endif; ?>
                                        <strong><?php echo htmlspecialchars($deal['name']); ?></strong>
                                    </div>
                                </td>
                                <td style="text-decoration:line-through; color:#999;">LKR <?php echo number_format($deal['original_price'], 0); ?></td>
                                <td><strong style="color:#003399;">LKR <?php echo number_format($deal['deal_price'], 0); ?></strong></td>
                                <td>
                                    <span class="status" style="background:#fef2f2; color:#ef4444;">
                                        <?php echo round((($deal['original_price'] - $deal['deal_price']) / $deal['original_price']) * 100); ?>% OFF
                                    </span>
                                </td>
                                <td style="font-size:12px; color:#666;">
                                    <?php if ($deal['start_date'] && $deal['end_date']): ?>
                                        <?php echo date('M d', strtotime($deal['start_date'])); ?> - <?php echo date('M d, Y', strtotime($deal['end_date'])); ?>
                                    <?php else: ?>
                                        <span style="color:#999;">No limit</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status <?php echo $deal['status']; ?>">
                                        <?php echo ucfirst($deal['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="deal_id" value="<?php echo $deal['id']; ?>">
                                        <button type="submit" class="btn-action edit" title="Toggle Status">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this deal? Product price will be restored to original.')">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="deal_id" value="<?php echo $deal['id']; ?>">
                                        <button type="submit" class="btn-action delete" title="Remove Deal">
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

                <!-- Promo Popup Image -->
                <div class="table-card" style="margin-top:24px;">
                    <div style="padding:24px;">
                        <h3 style="font-size:15px; font-weight:700; color:#1a1a2e; margin-bottom:6px; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-image" style="color:#003399;"></i> Promo Popup Image
                        </h3>
                        <p style="font-size:12px; color:#888; margin-bottom:18px;">Upload an image that will display as a popup overlay when customers visit the home page.</p>
                        
                        <form method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:16px;">
                            <input type="hidden" name="action" value="update_promo">
                            
                            <div style="display:flex; align-items:center; gap:12px;">
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:500; color:#333;">
                                    <input type="checkbox" name="promo_active" value="1" <?php echo $promoActive === '1' ? 'checked' : ''; ?> style="width:16px; height:16px; accent-color:#003399;">
                                    Enable Promo Popup
                                </label>
                            </div>

                            <?php if ($promoImage): ?>
                            <div style="display:flex; align-items:center; gap:16px; padding:14px; background:#f8f9fb; border-radius:10px; border:1px solid #eee;">
                                <img src="<?php echo htmlspecialchars(media_image_url($promoImage)); ?>" alt="Current Promo" style="width:120px; height:80px; object-fit:cover; border-radius:8px; border:1px solid #ddd;">
                                <div>
                                    <p style="font-size:12px; color:#333; font-weight:600; margin-bottom:4px;">Current promo image</p>
                                    <p style="font-size:11px; color:#888;"><?php echo basename($promoImage); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group" style="margin:0;">
                                <label style="display:block; font-size:11px; font-weight:600; color:#555; margin-bottom:4px;">Upload New Image</label>
                                <input type="file" name="promo_image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control-file">
                                <small class="form-help">Recommended: 600x800px or similar. JPG/PNG/WEBP/GIF. Max 5MB.</small>
                            </div>
                            
                            <div style="display:flex; gap:10px;">
                                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Settings</button>
                                <?php if ($promoImage): ?>
                                <button type="submit" name="action" value="remove_promo" class="btn-secondary" style="color:#ef4444; border-color:#fecaca;" onclick="return confirm('Remove promo popup image?')">
                                    <i class="fas fa-trash"></i> Remove Image
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Footer -->
                <div class="admin-footer">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo ADMIN_BRAND; ?>. All rights reserved.</p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
