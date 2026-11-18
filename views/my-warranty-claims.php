<?php
$pageTitle = 'My Warranty Claims - ARIGATO.LK';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/WarrantyClaimDAL.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$claims = [];
try {
    $claimDAL = new WarrantyClaimDAL();
    $claims = $claimDAL->getByUser($userId);
} catch (Throwable $e) {}

require_once __DIR__ . '/partials/header.php';
?>

<div class="breadcrumb-section">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>">Home</a>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <a href="<?php echo BASE_URL; ?>profile.php">My Account</a>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="breadcrumb-current">Warranty Claims</span>
        </nav>
    </div>
</div>

<section class="myorders-section">
    <div class="container">
        <div class="profile-page-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div>
                <h1>My Warranty Claims</h1>
                <p>Track the status of your warranty requests</p>
            </div>
            <a href="<?php echo BASE_URL; ?>warranty.php" class="myorders-back"><i class="fas fa-shield-alt"></i> Check Warranty</a>
        </div>

        <div class="profile-layout">
            <aside class="profile-sidebar">
                <div class="profile-sidebar-card">
                    <h3 class="profile-sidebar-title">My Account</h3>
                    <nav class="profile-nav">
                        <a href="<?php echo BASE_URL; ?>profile.php" class="profile-nav-item"><i class="fas fa-user"></i> My Profile</a>
                        <a href="<?php echo BASE_URL; ?>my-orders.php" class="profile-nav-item"><i class="fas fa-box"></i> My Orders</a>
                        <a href="<?php echo BASE_URL; ?>my-warranty-claims.php" class="profile-nav-item active"><i class="fas fa-shield-alt"></i> Warranty Claims</a>
                        <a href="<?php echo BASE_URL; ?>wishlist.php" class="profile-nav-item"><i class="far fa-heart"></i> Wishlist</a>
                        <a href="<?php echo BASE_URL; ?>profile.php?section=address" class="profile-nav-item"><i class="fas fa-map-marker-alt"></i> Address Book</a>
                        <a href="<?php echo BASE_URL; ?>profile.php?section=password" class="profile-nav-item"><i class="fas fa-lock"></i> Change Password</a>
                    </nav>
                </div>
            </aside>

            <main class="profile-main">
                <?php if (empty($claims)): ?>
                <div class="profile-card" style="text-align:center; padding:40px 24px;">
                    <i class="fas fa-shield-alt" style="font-size:2rem; color:#E5A91A; margin-bottom:12px;"></i>
                    <h3 style="margin:0 0 8px; color:#D4A017;">No warranty claims yet</h3>
                    <p style="color:#888; margin:0 0 18px;">Submit a claim from the warranty check page if your product is still under warranty.</p>
                    <a href="<?php echo BASE_URL; ?>warranty.php" style="display:inline-flex; align-items:center; gap:8px; padding:12px 22px; background:#D49A0A; color:#1a1a1a; border-radius:10px; text-decoration:none; font-weight:600; font-size:13px;">
                        <i class="fas fa-search"></i> Check Warranty
                    </a>
                </div>
                <?php else: ?>
                    <?php foreach ($claims as $claim):
                        $status = strtolower((string)($claim['status'] ?? 'pending'));
                    ?>
                    <div class="profile-card" style="margin-bottom:16px; border-left:4px solid <?php echo $status === 'replaced' ? '#16a34a' : ($status === 'approved' ? '#E5A91A' : ($status === 'rejected' ? '#ef4444' : '#ea580c')); ?>;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
                            <div>
                                <h3 style="margin:0 0 4px; font-size:16px; color:#D4A017;"><?php echo htmlspecialchars($claim['product_name']); ?></h3>
                                <p style="margin:0; font-size:12px; color:#888;">Claim #<?php echo htmlspecialchars($claim['claim_number']); ?></p>
                            </div>
                            <span style="font-size:11px; font-weight:700; text-transform:uppercase; padding:5px 12px; border-radius:20px; background:#f0f5fc; color:#D4A017;">
                                <?php echo htmlspecialchars(ezymart_warranty_claim_status_label($status)); ?>
                            </span>
                        </div>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; font-size:13px; color:#444;">
                            <div><strong>Issue:</strong> <?php echo htmlspecialchars($claim['issue_type']); ?></div>
                            <div><strong>Serial:</strong> <?php echo htmlspecialchars($claim['serial_number']); ?></div>
                            <div><strong>Submitted:</strong> <?php echo date('d M Y', strtotime($claim['created_at'])); ?></div>
                        </div>
                        <p style="margin:12px 0 0; font-size:13px; color:#555; line-height:1.6;"><?php echo nl2br(htmlspecialchars($claim['issue_description'])); ?></p>
                        <?php if ($status === 'approved' || $status === 'replaced'): ?>
                        <p style="margin:12px 0 0; font-size:12px; color:#16a34a;"><i class="fas fa-info-circle"></i> You may bring the product to our store or send it via courier service.</p>
                        <?php endif; ?>
                        <?php if ($status === 'rejected'): ?>
                            <p style="margin:12px 0 0; font-size:12px; color:#ef4444;"><i class="fas fa-times-circle"></i> Unfortunately, this claim was rejected. Please contact us for further assistance.</p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
