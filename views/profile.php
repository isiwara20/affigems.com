<?php
require_once __DIR__ . '/../config/app.php';
$pageTitle = "My Profile - " . SITE_NAME;

// Redirect if not logged in - BEFORE any output
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/../dal/OrderDAL.php';
require_once __DIR__ . '/../dal/WishlistDAL.php';
require_once __DIR__ . '/../dal/PointsDAL.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];

// Fetch user data
$user = null;
try {
    $database = new Database();
    $conn = $database->getConnection();

    // Ensure profile columns exist
    $profileColumns = [
        "phone VARCHAR(20) NULL",
        "dob DATE NULL",
        "address VARCHAR(255) NULL",
        "city VARCHAR(100) NULL",
        "district VARCHAR(100) NULL",
        "postal_code VARCHAR(10) NULL",
        "profile_photo VARCHAR(255) NULL",
        "email_updates TINYINT(1) DEFAULT 0",
        "updated_at DATETIME NULL"
    ];
    foreach ($profileColumns as $col) {
        try {
            $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS $col");
        } catch (Exception $e) {}
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    $user = null;
}

// Fetch account overview data
$totalOrders = 0;
$totalSpent = 0;
$wishlistCount = 0;
$rewardPoints = 0;

try {
    $orderDAL = new OrderDAL();
    $userOrders = $orderDAL->getByUser($userId);
    $totalOrders = count($userOrders);
    foreach ($userOrders as $order) {
        $totalSpent += $order['total'];
    }
} catch (Exception $e) {}

try {
    $wishlistDAL = new WishlistDAL();
    $wishlistCount = $wishlistDAL->getCount($userId);
} catch (Exception $e) {}

try {
    $pointsDAL = new PointsDAL();
    $rewardPoints = $pointsDAL->getUserPoints($userId);
} catch (Exception $e) {}

// Fetch default address for personal info section
require_once __DIR__ . '/../dal/AddressDAL.php';
$defaultAddress = null;
try {
    $addressDAL = new AddressDAL();
    $defaultAddress = $addressDAL->getDefault($userId);
} catch (Exception $e) {}

// Flash messages
$successMsg = $_SESSION['profile_success'] ?? '';
$errorMsg = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_success'], $_SESSION['profile_error']);

// Determine active section
$activeSection = $_GET['section'] ?? 'profile';
?>

<!-- Breadcrumb -->
<div class="breadcrumb-section">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>">Home</a>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <a href="<?php echo BASE_URL; ?>profile.php">My Account</a>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="breadcrumb-current">My Profile</span>
        </nav>
    </div>
</div>

<!-- Profile Page -->
<section class="profile-section">
    <div class="container">
        <div class="profile-page-header">
            <h1>My Profile</h1>
            <p>Manage your personal information and account settings</p>
        </div>

        <div class="profile-layout">
            <!-- Left Sidebar -->
            <aside class="profile-sidebar">
                <div class="profile-sidebar-card">
                    <h3 class="profile-sidebar-title">My Account</h3>
                    <nav class="profile-nav">
                        <a href="#" class="profile-nav-item <?php echo $activeSection === 'profile' ? 'active' : ''; ?>" data-section="section-profile">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="<?php echo BASE_URL; ?>my-orders.php" class="profile-nav-item">
                            <i class="fas fa-box"></i> My Orders
                        </a>
                        <a href="<?php echo BASE_URL; ?>my-warranty-claims.php" class="profile-nav-item">
                            <i class="fas fa-shield-alt"></i> Warranty Claims
                        </a>
                        <a href="<?php echo BASE_URL; ?>wishlist.php" class="profile-nav-item">
                            <i class="far fa-heart"></i> Wishlist
                        </a>
                        <a href="#" class="profile-nav-item <?php echo $activeSection === 'address' ? 'active' : ''; ?>" data-section="section-address">
                            <i class="fas fa-map-marker-alt"></i> Address Book
                        </a>
                        <a href="#" class="profile-nav-item" data-section="section-payment">
                            <i class="fas fa-credit-card"></i> Payment Methods
                        </a>
                        <a href="#" class="profile-nav-item <?php echo $activeSection === 'password' ? 'active' : ''; ?>" data-section="section-password">
                            <i class="fas fa-lock"></i> Change Password
                        </a>
                        <a href="#" class="profile-nav-item" data-section="section-notifications">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                        <a href="#" onclick="showLogoutConfirm(event)" class="profile-nav-item">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </nav>
                </div>

                <div class="profile-sidebar-help">
                    <h4>Need Help?</h4>
                    <p>We're here to help you</p>
                    <a href="#" class="profile-support-btn" onclick="event.preventDefault(); toggleChatbot();">
                        <i class="fas fa-headset"></i> Contact Support
                    </a>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="profile-main">

                <!-- Section: My Profile (Personal Information) -->
                <div id="section-profile" class="profile-content-section <?php echo $activeSection === 'profile' ? 'active' : ''; ?>">
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h2>Personal Information</h2>
                            <button type="button" class="btn-edit-profile" id="btnEditProfile" onclick="enableProfileEdit()">
                                <i class="fas fa-pen"></i> Edit Profile
                            </button>
                        </div>
                        <div class="profile-card-body">
                            <div class="profile-info-layout">
                                <!-- Profile Photo -->
                                <div class="profile-photo-section">
                                    <div class="profile-photo-wrapper">
                                        <?php
                                        $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($user['name'] ?? 'User') . '&size=150&background=1a1f4e&color=fff&bold=true&font-size=0.4';
                                        if (!empty($user['profile_photo'])) {
                                            $photoUrl = media_image_url('assets/uploads/profiles/' . $user['profile_photo']);
                                        }
                                        ?>
                                        <img src="<?php echo $photoUrl; ?>" alt="Profile Photo" class="profile-photo" id="profilePhotoPreview">
                                        <form action="<?php echo BASE_URL; ?>controllers/ProfileController.php" method="POST" enctype="multipart/form-data" id="photoForm">
                                            <input type="hidden" name="action" value="update_photo">
                                            <input type="file" name="profile_photo" id="profilePhotoInput" accept="image/*" style="display:none;" onchange="document.getElementById('photoForm').submit();">
                                        </form>
                                        <button type="button" class="photo-edit-btn photo-edit-hidden" id="photoEditBtn" onclick="document.getElementById('profilePhotoInput').click();">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Profile Form -->
                                <form action="<?php echo BASE_URL; ?>controllers/ProfileController.php" method="POST" class="profile-form" id="profileForm">
                                    <input type="hidden" name="action" value="update_profile">

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="full_name">Full Name</label>
                                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required disabled class="profile-input-readonly">
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email Address</label>
                                            <input type="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled class="profile-input-readonly">
                                            <span class="form-hint">Email address cannot be changed</span>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="phone">Phone Number</label>
                                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+94 71 234 5678" disabled class="profile-input-readonly">
                                        </div>
                                        <div class="form-group">
                                            <label for="dob">Date of Birth</label>
                                            <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>" disabled class="profile-input-readonly">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <?php
                                        $displayAddress = $defaultAddress['address'] ?? $user['address'] ?? '';
                                        $displayCity = $defaultAddress['city'] ?? $user['city'] ?? '';
                                        $displayDistrict = $defaultAddress['district'] ?? $user['district'] ?? '';
                                        $displayPostal = $defaultAddress['postal_code'] ?? $user['postal_code'] ?? '';
                                        ?>
                                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($displayAddress); ?>" placeholder="123, Main Street, Colombo 05" disabled class="profile-input-readonly">
                                    </div>

                                    <div class="form-row form-row-3">
                                        <div class="form-group">
                                            <label for="city">City</label>
                                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($displayCity); ?>" placeholder="Colombo" disabled class="profile-input-readonly">
                                        </div>
                                        <div class="form-group">
                                            <label for="district">District</label>
                                            <input type="text" id="district" name="district" value="<?php echo htmlspecialchars($displayDistrict); ?>" placeholder="Colombo" disabled class="profile-input-readonly">
                                        </div>
                                        <div class="form-group">
                                            <label for="postal_code">Postal Code</label>
                                            <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($displayPostal); ?>" placeholder="00500" disabled class="profile-input-readonly">
                                        </div>
                                    </div>

                                    <!-- Save / Cancel buttons (hidden by default) -->
                                    <div class="form-actions profile-edit-actions" id="profileEditActions" style="display:none;">
                                        <button type="button" class="btn-cancel-profile" onclick="cancelProfileEdit()">Cancel</button>
                                        <button type="submit" class="btn-save-profile">Save Changes</button>
                                    </div>

                                    <!-- Preferences -->
                                    <div class="profile-preferences">
                                        <h3>Preferences</h3>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="email_updates" id="emailUpdatesCheckbox" <?php echo (!empty($user['email_updates'])) ? 'checked' : ''; ?> disabled>
                                            <span class="checkmark"></span>
                                            Receive order updates via email
                                        </label>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Address Book -->
                <div id="section-address" class="profile-content-section <?php echo $activeSection === 'address' ? 'active' : ''; ?>">
                    <?php
                    // Fetch user addresses
                    $userAddresses = [];
                    try {
                        $addressDAL = new AddressDAL();
                        $userAddresses = $addressDAL->getByUser($userId);
                    } catch (Exception $e) {}
                    ?>

                    <!-- Saved Addresses -->
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h2>My Addresses</h2>
                            <button type="button" class="btn-edit-profile" onclick="toggleAddressForm()">
                                <i class="fas fa-plus"></i> Add New
                            </button>
                        </div>
                        <div class="profile-card-body">
                            <?php if (empty($userAddresses)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="fas fa-map-marker-alt"></i></div>
                                <h4>No addresses saved</h4>
                                <p>Add your delivery addresses for faster checkout.</p>
                                <button type="button" class="btn-add-address-empty" onclick="toggleAddressForm()">
                                    <i class="fas fa-plus"></i> Add Address
                                </button>
                            </div>
                            <?php else: ?>
                            <div class="address-grid">
                                <?php foreach ($userAddresses as $addr): ?>
                                <div class="address-card <?php echo $addr['is_default'] ? 'address-card-default' : ''; ?>">
                                    <div class="address-card-top">
                                        <div class="address-label">
                                            <i class="fas fa-<?php echo strtolower($addr['label']) === 'office' ? 'building' : 'home'; ?>"></i>
                                            <span><?php echo htmlspecialchars($addr['label']); ?></span>
                                            <?php if ($addr['is_default']): ?>
                                            <span class="address-default-badge">Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="address-card-actions">
                                            <?php if (!$addr['is_default']): ?>
                                            <a href="<?php echo BASE_URL; ?>controllers/ProfileController.php?action=set_default_address&id=<?php echo $addr['id']; ?>" class="address-action-btn" title="Set as Default">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button type="button" class="address-action-btn" onclick="editAddress(<?php echo htmlspecialchars(json_encode($addr)); ?>)" title="Edit">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <a href="<?php echo BASE_URL; ?>controllers/ProfileController.php?action=delete_address&id=<?php echo $addr['id']; ?>" class="address-action-btn address-action-delete" title="Delete" onclick="return confirm('Delete this address?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="address-card-body">
                                        <?php if (!empty($addr['full_name'])): ?>
                                        <p class="address-name"><?php echo htmlspecialchars($addr['full_name']); ?></p>
                                        <?php endif; ?>
                                        <p class="address-text"><?php echo htmlspecialchars($addr['address']); ?></p>
                                        <p class="address-text">
                                            <?php 
                                            $parts = array_filter([
                                                $addr['city'] ?? '',
                                                $addr['district'] ?? '',
                                                $addr['postal_code'] ?? ''
                                            ]);
                                            echo htmlspecialchars(implode(', ', $parts));
                                            ?>
                                        </p>
                                        <?php if (!empty($addr['phone'])): ?>
                                        <p class="address-phone"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($addr['phone']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Add / Edit Address Form (hidden by default) -->
                    <div class="profile-card address-form-card" id="addressFormCard" style="display:none;">
                        <div class="profile-card-header">
                            <h2 id="addressFormTitle">Add New Address</h2>
                            <button type="button" class="btn-edit-profile" onclick="toggleAddressForm()">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>
                        <div class="profile-card-body">
                            <form action="<?php echo BASE_URL; ?>controllers/ProfileController.php" method="POST" class="profile-form" id="addressForm">
                                <input type="hidden" name="action" value="add_address" id="addressFormAction">
                                <input type="hidden" name="address_id" value="" id="addressFormId">

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="addr_label">Address Label</label>
                                        <select id="addr_label" name="label" class="profile-select">
                                            <option value="Home">Home</option>
                                            <option value="Office">Office</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="addr_name">Full Name</label>
                                        <input type="text" id="addr_name" name="addr_name" placeholder="Recipient name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="addr_phone">Phone Number</label>
                                        <input type="text" id="addr_phone" name="addr_phone" placeholder="+94 71 234 5678" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <label class="checkbox-label" style="margin-top:8px;">
                                            <input type="checkbox" name="is_default" id="addr_default">
                                            <span class="checkmark"></span>
                                            Set as default address
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="addr_address">Street Address</label>
                                    <input type="text" id="addr_address" name="addr_address" placeholder="123, Main Street, Colombo 05" required>
                                </div>

                                <div class="form-row form-row-3">
                                    <div class="form-group">
                                        <label for="addr_city">City</label>
                                        <input type="text" id="addr_city" name="addr_city" placeholder="Colombo">
                                    </div>
                                    <div class="form-group">
                                        <label for="addr_district">District</label>
                                        <input type="text" id="addr_district" name="addr_district" placeholder="Colombo">
                                    </div>
                                    <div class="form-group">
                                        <label for="addr_postal">Postal Code</label>
                                        <input type="text" id="addr_postal" name="addr_postal" placeholder="00500">
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="button" class="btn-cancel-profile" onclick="toggleAddressForm()">Cancel</button>
                                    <button type="submit" class="btn-save-profile" id="addressSubmitBtn">Save Address</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Section: Change Password -->
                <div id="section-password" class="profile-content-section <?php echo $activeSection === 'password' ? 'active' : ''; ?>">
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h2>Change Password</h2>
                        </div>
                        <div class="profile-card-body">
                            <form action="<?php echo BASE_URL; ?>controllers/ProfileController.php" method="POST" class="profile-form">
                                <input type="hidden" name="action" value="change_password">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <div class="password-input-wrap">
                                        <input type="password" id="current_password" name="current_password" required placeholder="Enter current password">
                                        <button type="button" class="password-toggle-btn" onclick="togglePassword('current_password', this)">
                                            <i class="far fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <div class="password-input-wrap">
                                            <input type="password" id="new_password" name="new_password" required minlength="6" placeholder="Enter new password">
                                            <button type="button" class="password-toggle-btn" onclick="togglePassword('new_password', this)">
                                                <i class="far fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password</label>
                                        <div class="password-input-wrap">
                                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Confirm new password">
                                            <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password', this)">
                                                <i class="far fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn-save-profile">Update Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Section: Payment Methods -->
                <div id="section-payment" class="profile-content-section">
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h2>Payment Methods</h2>
                        </div>
                        <div class="profile-card-body">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="fas fa-credit-card"></i></div>
                                <h4>No payment methods saved</h4>
                                <p>Your saved payment methods will appear here for faster checkout.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Notifications -->
                <div id="section-notifications" class="profile-content-section">
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h2>Notification Preferences</h2>
                        </div>
                        <div class="profile-card-body">
                            <form action="<?php echo BASE_URL; ?>controllers/ProfileController.php" method="POST" class="profile-form">
                                <input type="hidden" name="action" value="update_profile">
                                <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
                                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                <input type="hidden" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>">
                                <input type="hidden" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                                <input type="hidden" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                                <input type="hidden" name="district" value="<?php echo htmlspecialchars($user['district'] ?? ''); ?>">
                                <input type="hidden" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>">

                                <div class="notification-options">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="email_updates" <?php echo (!empty($user['email_updates'])) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                        Receive order updates via email
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" checked disabled>
                                        <span class="checkmark"></span>
                                        Order confirmation emails (required)
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" checked disabled>
                                        <span class="checkmark"></span>
                                        Shipping updates (required)
                                    </label>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn-save-profile">Save Preferences</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </main>

            <!-- Right Sidebar - Account Overview -->
            <aside class="profile-overview">
                <div class="profile-overview-card">
                    <h3>Account Overview</h3>
                    <div class="overview-stats">
                        <div class="overview-stat-item">
                            <div class="overview-stat-icon"><i class="fas fa-box"></i></div>
                            <div class="overview-stat-info">
                                <span class="overview-stat-label">Total Orders</span>
                            </div>
                            <span class="overview-stat-value"><?php echo $totalOrders; ?></span>
                        </div>
                        <div class="overview-stat-item">
                            <div class="overview-stat-icon"><i class="far fa-heart"></i></div>
                            <div class="overview-stat-info">
                                <span class="overview-stat-label">Wishlist Items</span>
                            </div>
                            <span class="overview-stat-value"><?php echo $wishlistCount; ?></span>
                        </div>
                        <div class="overview-stat-item">
                            <div class="overview-stat-icon"><i class="fas fa-star"></i></div>
                            <div class="overview-stat-info">
                                <span class="overview-stat-label">Reward Points</span>
                            </div>
                            <span class="overview-stat-value"><?php echo number_format($rewardPoints); ?> pts</span>
                        </div>
                        <div class="overview-stat-item">
                            <div class="overview-stat-icon"><i class="fas fa-wallet"></i></div>
                            <div class="overview-stat-info">
                                <span class="overview-stat-label">Total Spent</span>
                            </div>
                            <span class="overview-stat-value">LKR <?php echo number_format($totalSpent, 0); ?></span>
                        </div>
                    </div>
                    <div class="overview-meta">
                        <div class="overview-meta-item">
                            <i class="far fa-calendar-alt"></i>
                            <span>Member Since</span>
                            <strong><?php echo isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A'; ?></strong>
                        </div>
                        <div class="overview-meta-item">
                            <i class="fas fa-circle" style="color: #10b981; font-size: 8px;"></i>
                            <span>Account Status</span>
                            <strong class="status-badge status-active">Active</strong>
                        </div>
                    </div>
                </div>

                <!-- Security Notice -->
                <div class="profile-security-card">
                    <div class="security-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="security-content">
                        <h4>Your data is safe with us</h4>
                        <p>We use secure encryption to protect your personal information.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<script>
(function() {
    // Section switching via sidebar nav
    var navItems = document.querySelectorAll('.profile-nav-item[data-section]');
    var sections = document.querySelectorAll('.profile-content-section');

    function showSection(sectionId) {
        sections.forEach(function(s) { s.classList.remove('active'); });
        var target = document.getElementById(sectionId);
        if (target) target.classList.add('active');
        navItems.forEach(function(item) { item.classList.remove('active'); });
        var activeNav = document.querySelector('.profile-nav-item[data-section="' + sectionId + '"]');
        if (activeNav) activeNav.classList.add('active');
    }

    navItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            var sectionId = this.getAttribute('data-section');
            if (sectionId) {
                showSection(sectionId);
            }
        });
    });

    var hasActive = document.querySelector('.profile-content-section.active');
    if (!hasActive) {
        showSection('section-profile');
    }
})();

// ---- Profile Edit Toggle ----
function enableProfileEdit() {
    var form = document.getElementById('profileForm');
    var inputs = form.querySelectorAll('input.profile-input-readonly');
    inputs.forEach(function(input) {
        if (input.id !== 'email') {
            input.disabled = false;
            input.classList.remove('profile-input-readonly');
        }
    });
    // Enable checkbox
    var checkbox = document.getElementById('emailUpdatesCheckbox');
    if (checkbox) checkbox.disabled = false;
    // Show save/cancel, hide edit button
    document.getElementById('profileEditActions').style.display = 'flex';
    document.getElementById('btnEditProfile').style.display = 'none';
    // Show photo edit button
    document.getElementById('photoEditBtn').classList.remove('photo-edit-hidden');
}

function cancelProfileEdit() {
    var form = document.getElementById('profileForm');
    form.reset();
    var inputs = form.querySelectorAll('input[name]');
    inputs.forEach(function(input) {
        if (input.type !== 'hidden' && input.id !== 'email') {
            input.disabled = true;
            input.classList.add('profile-input-readonly');
        }
    });
    var checkbox = document.getElementById('emailUpdatesCheckbox');
    if (checkbox) checkbox.disabled = true;
    document.getElementById('profileEditActions').style.display = 'none';
    document.getElementById('btnEditProfile').style.display = 'inline-flex';
    document.getElementById('photoEditBtn').classList.add('photo-edit-hidden');
}

// ---- Password Toggle ----
function togglePassword(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// ---- Address Form Toggle ----
function toggleAddressForm() {
    var card = document.getElementById('addressFormCard');
    if (card.style.display === 'none') {
        card.style.display = 'block';
        resetAddressForm();
        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        card.style.display = 'none';
        resetAddressForm();
    }
}

function resetAddressForm() {
    var form = document.getElementById('addressForm');
    form.reset();
    document.getElementById('addressFormAction').value = 'add_address';
    document.getElementById('addressFormId').value = '';
    document.getElementById('addressFormTitle').textContent = 'Add New Address';
    document.getElementById('addressSubmitBtn').textContent = 'Save Address';
    document.getElementById('addr_name').value = '<?php echo htmlspecialchars($user['name'] ?? '', ENT_QUOTES); ?>';
    document.getElementById('addr_phone').value = '<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES); ?>';
}

function editAddress(addr) {
    var card = document.getElementById('addressFormCard');
    card.style.display = 'block';
    document.getElementById('addressFormAction').value = 'update_address';
    document.getElementById('addressFormId').value = addr.id;
    document.getElementById('addressFormTitle').textContent = 'Edit Address';
    document.getElementById('addressSubmitBtn').textContent = 'Update Address';
    document.getElementById('addr_label').value = addr.label || 'Home';
    document.getElementById('addr_name').value = addr.full_name || '';
    document.getElementById('addr_phone').value = addr.phone || '';
    document.getElementById('addr_address').value = addr.address || '';
    document.getElementById('addr_city').value = addr.city || '';
    document.getElementById('addr_district').value = addr.district || '';
    document.getElementById('addr_postal').value = addr.postal_code || '';
    document.getElementById('addr_default').checked = addr.is_default == 1;
    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    var alerts = document.querySelectorAll('.profile-alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(100%)';
            setTimeout(function() { alert.remove(); }, 400);
        }, 4500);
    });
});
</script>

<?php if ($successMsg): ?>
<div class="profile-alert profile-alert-success">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMsg); ?>
</div>
<?php endif; ?>

<?php if ($errorMsg): ?>
<div class="profile-alert profile-alert-error">
    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMsg); ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
