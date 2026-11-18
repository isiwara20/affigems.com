<?php
$currentPage = 'site-settings';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/SiteSettingsDAL.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$settingsDAL = new SiteSettingsDAL();
$adminName = $_SESSION['user_name'] ?? 'Admin';
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingsDAL->set('low_stock_email', trim($_POST['low_stock_email'] ?? ''));
    $settingsDAL->set('low_stock_threshold', trim($_POST['low_stock_threshold'] ?? '5'));
    $settingsDAL->set('order_notification_email', trim($_POST['order_notification_email'] ?? ''));
    $settingsDAL->set('store_name', trim($_POST['store_name'] ?? 'ARIGATO.LK'));
    $settingsDAL->set('store_phone', trim($_POST['store_phone'] ?? ''));
    $settingsDAL->set('store_email', trim($_POST['store_email'] ?? ''));
    $settingsDAL->set('store_address', trim($_POST['store_address'] ?? ''));
    $settingsDAL->set('whatsapp_number', trim($_POST['whatsapp_number'] ?? ''));

    $settingsDAL->set('sms_enabled', isset($_POST['sms_enabled']) ? '1' : '0');
    $smsProvider = trim($_POST['sms_api_provider'] ?? 'notify_lk');
    if (!in_array($smsProvider, ['notify_lk', 'shoutout'], true)) {
        $smsProvider = 'notify_lk';
    }
    $settingsDAL->set('sms_api_provider', $smsProvider);
    $settingsDAL->set('sms_api_user_id', trim($_POST['sms_api_user_id'] ?? ''));
    $settingsDAL->set('sms_api_key', trim($_POST['sms_api_key'] ?? ''));
    $settingsDAL->set('sms_sender_id', trim($_POST['sms_sender_id'] ?? ''));
    $settingsDAL->set('sms_shoutout_api_key', trim($_POST['sms_shoutout_api_key'] ?? ''));
    $settingsDAL->set('sms_shoutout_source', trim($_POST['sms_shoutout_source'] ?? ''));
    $settingsDAL->set('order_notification_sms', trim($_POST['order_notification_sms'] ?? ''));
    $settingsDAL->set('low_stock_sms', trim($_POST['low_stock_sms'] ?? ''));
    $settingsDAL->set('sms_order_placed_enabled', isset($_POST['sms_order_placed_enabled']) ? '1' : '0');
    $settingsDAL->set('sms_order_status_enabled', isset($_POST['sms_order_status_enabled']) ? '1' : '0');
    $settingsDAL->set('sms_admin_new_order_enabled', isset($_POST['sms_admin_new_order_enabled']) ? '1' : '0');
    $settingsDAL->set('sms_admin_low_stock_enabled', isset($_POST['sms_admin_low_stock_enabled']) ? '1' : '0');

    $settingsDAL->set('google_analytics_enabled', isset($_POST['google_analytics_enabled']) ? '1' : '0');
    $settingsDAL->set('google_analytics_id', trim($_POST['google_analytics_id'] ?? ''));
    
    $message = 'Settings saved successfully!';
    $messageType = 'success';
}

// Load current settings
$settings = $settingsDAL->getAll();
$lowStockEmail = $settings['low_stock_email'] ?? '';
$lowStockThreshold = $settings['low_stock_threshold'] ?? '5';
$storeName = $settings['store_name'] ?? 'ARIGATO.LK';
$storePhone = $settings['store_phone'] ?? '';
$storeEmail = $settings['store_email'] ?? '';
$storeAddress = $settings['store_address'] ?? '';
$whatsappNumber = $settings['whatsapp_number'] ?? '';
$smsEnabled = ($settings['sms_enabled'] ?? '0') === '1';
$smsProvider = $settings['sms_api_provider'] ?? 'notify_lk';
if (!in_array($smsProvider, ['notify_lk', 'shoutout'], true)) {
    $smsProvider = 'notify_lk';
}
$smsUserId = $settings['sms_api_user_id'] ?? '';
$smsApiKey = $settings['sms_api_key'] ?? '';
$smsSenderId = $settings['sms_sender_id'] ?? '';
$smsShoutoutApiKey = $settings['sms_shoutout_api_key'] ?? '';
$smsShoutoutSource = $settings['sms_shoutout_source'] ?? '';
$orderNotificationSms = $settings['order_notification_sms'] ?? '';
$lowStockSms = $settings['low_stock_sms'] ?? '';
$smsOrderPlaced = ($settings['sms_order_placed_enabled'] ?? '1') === '1';
$smsOrderStatus = ($settings['sms_order_status_enabled'] ?? '1') === '1';
$smsAdminNewOrder = ($settings['sms_admin_new_order_enabled'] ?? '1') === '1';
$smsAdminLowStock = ($settings['sms_admin_low_stock_enabled'] ?? '1') === '1';
$gaEnabled = ($settings['google_analytics_enabled'] ?? '0') === '1';
$gaMeasurementId = $settings['google_analytics_id'] ?? '';
$inputStyle = "width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Site Settings - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/partials/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/partials/topbar.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Site Settings</h1>
                        <p class="page-subtitle">Manage your store configuration</p>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="admin-toast <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- Store Info -->
                    <div class="content-card" style="background:#fff; border:1px solid #eee; border-radius:14px; padding:28px; margin-bottom:20px;">
                        <h3 style="font-size:15px; font-weight:700; color:#0d1137; margin-bottom:20px; padding-bottom:14px; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-store" style="color:#e8600a;"></i> Store Information
                        </h3>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group" style="margin-bottom:16px;">
                                <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Store Name</label>
                                <input type="text" name="store_name" value="<?php echo htmlspecialchars($storeName); ?>" style="width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;">
                            </div>
                            <div class="form-group" style="margin-bottom:16px;">
                                <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Store Phone</label>
                                <input type="text" name="store_phone" value="<?php echo htmlspecialchars($storePhone); ?>" placeholder="077 899 8990" style="width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;">
                            </div>
                            <div class="form-group" style="margin-bottom:16px;">
                                <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Store Email</label>
                                <input type="email" name="store_email" value="<?php echo htmlspecialchars($storeEmail); ?>" placeholder="info@arigato.lk" style="width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;">
                            </div>
                            <div class="form-group" style="margin-bottom:16px;">
                                <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">WhatsApp Number</label>
                                <input type="text" name="whatsapp_number" value="<?php echo htmlspecialchars($whatsappNumber); ?>" placeholder="+94778998990" style="width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Store Address</label>
                            <input type="text" name="store_address" value="<?php echo htmlspecialchars($storeAddress); ?>" placeholder="779/3, Pannipitiya Road, Pelawatta, Battaramulla" style="width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;">
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="content-card" style="background:#fff; border:1px solid #eee; border-radius:14px; padding:28px; margin-bottom:20px;">
                        <h3 style="font-size:15px; font-weight:700; color:#0d1137; margin-bottom:20px; padding-bottom:14px; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-bell" style="color:#e8600a;"></i> Notification Settings
                        </h3>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group" style="margin-bottom:16px;">
                                <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Low Stock Alert Email <span style="color:#ef4444;">*</span></label>
                                <input type="email" name="low_stock_email" value="<?php echo htmlspecialchars($lowStockEmail); ?>" placeholder="admin@example.com" style="width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;" required>
                                <p style="font-size:11px; color:#6b7099; margin-top:4px;">Email to receive low stock warnings</p>
                            </div>
                            <div class="form-group" style="margin-bottom:16px;">
                                <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Low Stock Threshold</label>
                                <input type="number" name="low_stock_threshold" value="<?php echo htmlspecialchars($lowStockThreshold); ?>" min="1" max="50" style="width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;">
                                <p style="font-size:11px; color:#6b7099; margin-top:4px;">Alert when stock falls below this number</p>
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">New Order Notification Email</label>
                            <input type="email" name="order_notification_email" value="<?php echo htmlspecialchars($settings['order_notification_email'] ?? ''); ?>" placeholder="orders@example.com" style="width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;">
                            <p style="font-size:11px; color:#6b7099; margin-top:4px;">Email to receive new order notifications (leave empty to disable)</p>
                        </div>
                    </div>

                    <!-- SMS (Notify.lk / ShoutOUT) -->
                    <div class="content-card" style="background:#fff; border:1px solid #eee; border-radius:14px; padding:28px; margin-bottom:20px;">
                        <h3 style="font-size:15px; font-weight:700; color:#0d1137; margin-bottom:20px; padding-bottom:14px; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-sms" style="color:#E0A810;"></i> SMS Notifications
                        </h3>
                        <label style="display:flex; align-items:center; gap:10px; margin-bottom:18px; cursor:pointer;">
                            <input type="checkbox" name="sms_enabled" value="1" <?php echo $smsEnabled ? 'checked' : ''; ?> style="width:18px; height:18px;">
                            <span style="font-size:13px; font-weight:600; color:#333;">Enable SMS notifications</span>
                        </label>

                        <div class="form-group" style="margin-bottom:20px;">
                            <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:8px;">SMS Gateway</label>
                            <div style="display:flex; flex-wrap:wrap; gap:12px;">
                                <label style="display:flex; align-items:center; gap:8px; padding:10px 16px; border:1.5px solid <?php echo $smsProvider === 'notify_lk' ? '#E0A810' : '#e4e8f0'; ?>; border-radius:10px; cursor:pointer; background:<?php echo $smsProvider === 'notify_lk' ? '#f0f6ff' : '#fff'; ?>;">
                                    <input type="radio" name="sms_api_provider" value="notify_lk" <?php echo $smsProvider === 'notify_lk' ? 'checked' : ''; ?> class="sms-provider-radio">
                                    <span style="font-size:13px; font-weight:600; color:#333;">Notify.lk</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; padding:10px 16px; border:1.5px solid <?php echo $smsProvider === 'shoutout' ? '#E0A810' : '#e4e8f0'; ?>; border-radius:10px; cursor:pointer; background:<?php echo $smsProvider === 'shoutout' ? '#f0f6ff' : '#fff'; ?>;">
                                    <input type="radio" name="sms_api_provider" value="shoutout" <?php echo $smsProvider === 'shoutout' ? 'checked' : ''; ?> class="sms-provider-radio">
                                    <span style="font-size:13px; font-weight:600; color:#333;">ShoutOUT</span>
                                </label>
                            </div>
                            <p style="font-size:11px; color:#6b7099; margin-top:8px;">Choose which gateway sends order alerts, OTPs, and campaigns. Credentials for both can be saved; only the selected gateway is used.</p>
                        </div>

                        <div id="sms-notify-panel" style="display:<?php echo $smsProvider === 'notify_lk' ? 'block' : 'none'; ?>; margin-bottom:20px; padding:18px; background:#f8fafc; border-radius:12px; border:1px solid #e8ecf4;">
                            <h4 style="font-size:13px; font-weight:700; color:#0d1137; margin:0 0 14px;">Notify.lk API</h4>
                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                                <div class="form-group">
                                    <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">User ID</label>
                                    <input type="text" name="sms_api_user_id" value="<?php echo htmlspecialchars($smsUserId); ?>" placeholder="From Notify.lk settings" style="<?php echo $inputStyle; ?>">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">API Key</label>
                                    <input type="password" name="sms_api_key" value="<?php echo htmlspecialchars($smsApiKey); ?>" placeholder="From Notify.lk settings" style="<?php echo $inputStyle; ?>">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Sender ID</label>
                                    <input type="text" name="sms_sender_id" value="<?php echo htmlspecialchars($smsSenderId); ?>" placeholder="e.g. ARIGATO" style="<?php echo $inputStyle; ?>">
                                    <p style="font-size:11px; color:#6b7099; margin-top:4px;">Approved sender ID from <a href="https://www.notify.lk/" target="_blank" rel="noopener">Notify.lk</a> (case sensitive)</p>
                                </div>
                            </div>
                        </div>

                        <div id="sms-shoutout-panel" style="display:<?php echo $smsProvider === 'shoutout' ? 'block' : 'none'; ?>; margin-bottom:20px; padding:18px; background:#f8fafc; border-radius:12px; border:1px solid #e8ecf4;">
                            <h4 style="font-size:13px; font-weight:700; color:#0d1137; margin:0 0 14px;">ShoutOUT API</h4>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                                <div class="form-group">
                                    <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">API Key</label>
                                    <input type="password" name="sms_shoutout_api_key" value="<?php echo htmlspecialchars($smsShoutoutApiKey); ?>" placeholder="From ShoutOUT dashboard" style="<?php echo $inputStyle; ?>">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Source / Sender</label>
                                    <input type="text" name="sms_shoutout_source" value="<?php echo htmlspecialchars($smsShoutoutSource); ?>" placeholder="e.g. ShoutDEMO" style="<?php echo $inputStyle; ?>">
                                    <p style="font-size:11px; color:#6b7099; margin-top:4px;">Your approved source name from <a href="https://www.getshoutout.com/" target="_blank" rel="noopener">ShoutOUT</a> (demo: <code>ShoutDEMO</code>)</p>
                                </div>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                            <div class="form-group">
                                <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">New Order Alert SMS Number(s)</label>
                                <input type="text" name="order_notification_sms" value="<?php echo htmlspecialchars($orderNotificationSms); ?>" placeholder="94774370039, 94714370039" style="<?php echo $inputStyle; ?>">
                                <p style="font-size:11px; color:#6b7099; margin-top:4px;">Comma-separated. SMS when a new order is placed.</p>
                            </div>
                            <div class="form-group">
                                <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Low Stock Alert SMS Number(s)</label>
                                <input type="text" name="low_stock_sms" value="<?php echo htmlspecialchars($lowStockSms); ?>" placeholder="94774370039" style="<?php echo $inputStyle; ?>">
                                <p style="font-size:11px; color:#6b7099; margin-top:4px;">Comma-separated. SMS when product stock is low.</p>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                            <label style="display:flex; align-items:center; gap:8px; font-size:12px; color:#333; cursor:pointer;">
                                <input type="checkbox" name="sms_order_placed_enabled" value="1" <?php echo $smsOrderPlaced ? 'checked' : ''; ?>> Customer SMS when order is placed
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; font-size:12px; color:#333; cursor:pointer;">
                                <input type="checkbox" name="sms_order_status_enabled" value="1" <?php echo $smsOrderStatus ? 'checked' : ''; ?>> Customer SMS on order status updates
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; font-size:12px; color:#333; cursor:pointer;">
                                <input type="checkbox" name="sms_admin_new_order_enabled" value="1" <?php echo $smsAdminNewOrder ? 'checked' : ''; ?>> Admin SMS for new orders
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; font-size:12px; color:#333; cursor:pointer;">
                                <input type="checkbox" name="sms_admin_low_stock_enabled" value="1" <?php echo $smsAdminLowStock ? 'checked' : ''; ?>> Admin SMS for low stock
                            </label>
                        </div>
                        <p style="font-size:11px; color:#6b7099; margin-top:14px;">
                            <i class="fas fa-info-circle"></i> Active gateway: <strong><?php echo htmlspecialchars($smsProvider === 'shoutout' ? 'ShoutOUT' : 'Notify.lk'); ?></strong>.
                            Notify.lk docs: <a href="https://developer.notify.lk/api-endpoints/" target="_blank" rel="noopener">developer.notify.lk</a>.
                            ShoutOUT: <a href="https://www.getshoutout.com/" target="_blank" rel="noopener">getshoutout.com</a>.
                            <a href="<?php echo BASE_URL; ?>admin/sms-campaign.php" style="color:#E0A810; font-weight:600;">Send SMS campaign &rarr;</a>
                        </p>
                    </div>

                    <!-- Google Analytics -->
                    <div class="content-card" style="background:#fff; border:1px solid #eee; border-radius:14px; padding:28px; margin-bottom:20px;">
                        <h3 style="font-size:15px; font-weight:700; color:#0d1137; margin-bottom:20px; padding-bottom:14px; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-chart-line" style="color:#E0A810;"></i> Google Analytics (GA4)
                        </h3>
                        <label style="display:flex; align-items:center; gap:10px; margin-bottom:18px; cursor:pointer;">
                            <input type="checkbox" name="google_analytics_enabled" value="1" <?php echo $gaEnabled ? 'checked' : ''; ?> style="width:18px; height:18px;">
                            <span style="font-size:13px; font-weight:600; color:#333;">Enable Google Analytics tracking</span>
                        </label>
                        <div class="form-group" style="max-width:420px; margin-bottom:0;">
                            <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Measurement ID</label>
                            <input type="text" name="google_analytics_id" value="<?php echo htmlspecialchars($gaMeasurementId); ?>" placeholder="G-XXXXXXXXXX" style="<?php echo $inputStyle; ?>">
                            <p style="font-size:11px; color:#6b7099; margin-top:6px; line-height:1.6;">
                                Create a property at <a href="https://analytics.google.com/" target="_blank" rel="noopener">Google Analytics</a>,
                                then copy the <strong>Measurement ID</strong> (starts with <code>G-</code>) from
                                Admin &rarr; Data streams &rarr; your web stream.
                                Tracking runs on the storefront only (not the admin panel).
                            </p>
                        </div>
                    </div>

                    <button type="submit" style="padding:13px 30px; background:#8A6508; color:#fff; border:none; border-radius:10px; font-family:'Poppins',sans-serif; font-size:14px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </form>

                <div class="admin-footer" style="margin-top:40px;">
                    <p>&copy; 2026 ARIGATO.LK. All rights reserved.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.querySelector('.admin-layout').classList.toggle('sidebar-collapsed');
    });

    (function() {
        var notifyPanel = document.getElementById('sms-notify-panel');
        var shoutoutPanel = document.getElementById('sms-shoutout-panel');
        var radios = document.querySelectorAll('.sms-provider-radio');

        function updateSmsPanels() {
            var selected = document.querySelector('input[name="sms_api_provider"]:checked');
            var provider = selected ? selected.value : 'notify_lk';
            if (notifyPanel) notifyPanel.style.display = provider === 'notify_lk' ? 'block' : 'none';
            if (shoutoutPanel) shoutoutPanel.style.display = provider === 'shoutout' ? 'block' : 'none';
        }

        radios.forEach(function(radio) {
            radio.addEventListener('change', updateSmsPanels);
        });
    })();
    </script>
</body>
</html>
