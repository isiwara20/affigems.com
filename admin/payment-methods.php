<?php
$currentPage = 'payment-methods';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
require_once __DIR__ . '/../helpers/payment.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$settingsDAL = new SiteSettingsDAL();
$adminName = $_SESSION['user_name'] ?? 'Admin';
$message = '';
$gatewayLabels = ezymart_payment_gateway_labels();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingsDAL->set('payment_cod_enabled', isset($_POST['cod_enabled']) ? '1' : '0');
    $settingsDAL->set('payment_card_enabled', isset($_POST['card_enabled']) ? '1' : '0');
    $settingsDAL->set('payment_card_gateway', trim($_POST['payment_card_gateway'] ?? 'directpay'));

    $settingsDAL->set('payment_directpay_enabled', isset($_POST['payment_directpay_enabled']) ? '1' : '0');
    $settingsDAL->set('directpay_merchant_id', trim($_POST['directpay_merchant_id'] ?? ''));
    $settingsDAL->set('directpay_secret_key', trim($_POST['directpay_secret_key'] ?? ''));
    $settingsDAL->set('directpay_stage', ($_POST['directpay_stage'] ?? 'PROD') === 'SANDBOX' ? 'SANDBOX' : 'PROD');
    $settingsDAL->set('directpay_surcharge_percent', (string)max(0, (float)($_POST['directpay_surcharge_percent'] ?? 0)));

    $settingsDAL->set('payment_payhere_enabled', isset($_POST['payment_payhere_enabled']) ? '1' : '0');
    $settingsDAL->set('payhere_merchant_id', trim($_POST['payhere_merchant_id'] ?? ''));
    $settingsDAL->set('payhere_merchant_secret', trim($_POST['payhere_merchant_secret'] ?? ''));
    $settingsDAL->set('payhere_sandbox', isset($_POST['payhere_sandbox']) ? '1' : '0');
    $settingsDAL->set('payhere_surcharge_percent', (string)max(0, (float)($_POST['payhere_surcharge_percent'] ?? 0)));

    $settingsDAL->set('payment_payzy_enabled', isset($_POST['payment_payzy_enabled']) ? '1' : '0');
    $settingsDAL->set('payzy_merchant_id', trim($_POST['payzy_merchant_id'] ?? ''));
    $settingsDAL->set('payzy_api_key', trim($_POST['payzy_api_key'] ?? ''));
    $settingsDAL->set('payzy_api_secret', trim($_POST['payzy_api_secret'] ?? ''));
    $settingsDAL->set('payzy_api_base_url', trim($_POST['payzy_api_base_url'] ?? 'https://api.payzy.lk'));
    $settingsDAL->set('payzy_checkout_path', trim($_POST['payzy_checkout_path'] ?? '/api/v1/checkout/session'));
    $settingsDAL->set('payzy_sandbox', isset($_POST['payzy_sandbox']) ? '1' : '0');
    $settingsDAL->set('payzy_surcharge_percent', (string)max(0, (float)($_POST['payzy_surcharge_percent'] ?? 0)));

    $settingsDAL->set('payment_paykoko_enabled', isset($_POST['payment_paykoko_enabled']) ? '1' : '0');
    $settingsDAL->set('paykoko_merchant_id', trim($_POST['paykoko_merchant_id'] ?? ''));
    $settingsDAL->set('paykoko_api_key', trim($_POST['paykoko_api_key'] ?? ''));
    $settingsDAL->set('paykoko_public_key', trim($_POST['paykoko_public_key'] ?? ''));
    $settingsDAL->set('paykoko_private_key', trim($_POST['paykoko_private_key'] ?? ''));
    $settingsDAL->set('paykoko_api_base_url', trim($_POST['paykoko_api_base_url'] ?? ''));
    $settingsDAL->set('paykoko_checkout_path', trim($_POST['paykoko_checkout_path'] ?? '/merchants/orderInit'));
    $settingsDAL->set('paykoko_sandbox', isset($_POST['paykoko_sandbox']) ? '1' : '0');
    $settingsDAL->set('paykoko_surcharge_percent', (string)max(0, (float)($_POST['paykoko_surcharge_percent'] ?? 13)));

    $message = 'Payment settings saved successfully!';
    ezymart_payment_settings(true);
}

$settings = ezymart_payment_settings(true);
$codEnabled = $settings['cod_enabled'] ? '1' : '0';
$cardEnabled = $settings['card_enabled'] ? '1' : '0';
$activeGateway = $settings['active_gateway'];
$g = $settings['gateways'];

$callbackExamples = [];
foreach (ezymart_payment_gateway_ids() as $gid) {
    $callbackExamples[$gid] = [
        'return' => ezymart_payment_callback_url($gid, 'return'),
        'notify' => ezymart_payment_notify_url($gid),
        'cancel' => ezymart_payment_callback_url($gid, 'cancel'),
    ];
}

$inputStyle = 'width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:Poppins,sans-serif; font-size:13px;';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Payment Methods - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .payment-card { background:#fff; border:1px solid #eee; border-radius:14px; padding:24px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; transition:all 0.2s; }
        .payment-card:hover { border-color:#1a1f4e; }
        .payment-card-left { display:flex; align-items:center; gap:16px; }
        .payment-card-icon { width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .payment-card-icon.cod { background:#fff7ed; color:#ea580c; }
        .payment-card-icon.card { background:#eff6ff; color:#2563eb; }
        .payment-card-info h3 { font-size:15px; font-weight:600; color:#0d1137; margin-bottom:2px; }
        .payment-card-info p { font-size:12px; color:#6b7099; margin:0; }
        .toggle-switch { position:relative; width:50px; height:26px; }
        .toggle-switch input { opacity:0; width:0; height:0; }
        .toggle-slider { position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background:#e4e8f0; border-radius:26px; transition:0.3s; }
        .toggle-slider:before { content:''; position:absolute; height:20px; width:20px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:0.3s; box-shadow:0 1px 3px rgba(0,0,0,0.15); }
        .toggle-switch input:checked + .toggle-slider { background:#16a34a; }
        .toggle-switch input:checked + .toggle-slider:before { transform:translateX(24px); }
        .payment-status { font-size:11px; font-weight:600; padding:4px 10px; border-radius:20px; margin-left:12px; }
        .payment-status.enabled { background:#ecfdf5; color:#059669; }
        .payment-status.disabled { background:#fef2f2; color:#ef4444; }
        .gateway-panel { background:#fff; border:1px solid #eee; border-radius:14px; padding:24px; margin-bottom:18px; }
        .gateway-panel h3 { font-size:16px; font-weight:700; color:#0d1137; margin:0 0 6px; display:flex; align-items:center; gap:10px; }
        .gateway-panel .gateway-desc { font-size:12px; color:#6b7099; margin-bottom:18px; }
        .gateway-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .gateway-grid .full { grid-column:1 / -1; }
        .form-group label { display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px; }
        .form-help { display:block; font-size:11px; color:#888; margin-top:4px; }
        .callback-box { background:#f8f9fc; border:1px solid #e8ecf1; border-radius:10px; padding:12px 14px; font-size:11px; color:#555; line-height:1.7; word-break:break-all; }
        .section-title { font-size:18px; font-weight:700; color:#0d1137; margin:28px 0 14px; }
        @media (max-width: 900px) { .gateway-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/partials/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/partials/topbar.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Payment Methods</h1>
                        <p class="page-subtitle">Enable payment options and configure gateway API credentials</p>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="admin-toast success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="payment-card">
                        <div class="payment-card-left">
                            <div class="payment-card-icon cod"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="payment-card-info">
                                <h3>Cash on Delivery <span class="payment-status <?php echo $codEnabled === '1' ? 'enabled' : 'disabled'; ?>"><?php echo $codEnabled === '1' ? 'Active' : 'Disabled'; ?></span></h3>
                                <p>Customers pay in cash when they receive their order</p>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="cod_enabled" <?php echo $codEnabled === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="payment-card">
                        <div class="payment-card-left">
                            <div class="payment-card-icon card"><i class="fas fa-credit-card"></i></div>
                            <div class="payment-card-info">
                                <h3>Online Payments <span class="payment-status <?php echo $cardEnabled === '1' ? 'enabled' : 'disabled'; ?>"><?php echo $cardEnabled === '1' ? 'Active' : 'Disabled'; ?></span></h3>
                                <p>DirectPay, PayHere, Payzy, and PayKoko — configure each gateway below</p>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="card_enabled" <?php echo $cardEnabled === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="gateway-panel">
                        <h3><i class="fas fa-star" style="color:#f59e0b;"></i> Default Online Gateway</h3>
                        <p class="gateway-desc">Used when the customer selects a generic card payment or when only one gateway is enabled.</p>
                        <div class="form-group">
                            <select name="payment_card_gateway" style="<?php echo $inputStyle; ?>">
                                <?php foreach ($gatewayLabels as $id => $label): ?>
                                <option value="<?php echo htmlspecialchars($id); ?>" <?php echo $activeGateway === $id ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h2 class="section-title">Gateway API Settings</h2>

                    <!-- DirectPay -->
                    <div class="gateway-panel">
                        <h3>
                            DirectPay
                            <label class="toggle-switch" style="margin-left:auto;">
                                <input type="checkbox" name="payment_directpay_enabled" <?php echo !empty($g['directpay']['enabled']) ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </h3>
                        <p class="gateway-desc">Card payments via <a href="https://directpay.lk/" target="_blank" rel="noopener">directpay.lk</a></p>
                        <div class="gateway-grid">
                            <div class="form-group">
                                <label>Merchant ID</label>
                                <input type="text" name="directpay_merchant_id" value="<?php echo htmlspecialchars($g['directpay']['merchant_id']); ?>" style="<?php echo $inputStyle; ?>">
                            </div>
                            <div class="form-group">
                                <label>Environment</label>
                                <select name="directpay_stage" style="<?php echo $inputStyle; ?>">
                                    <option value="PROD" <?php echo ($g['directpay']['stage'] ?? 'PROD') === 'PROD' ? 'selected' : ''; ?>>Production</option>
                                    <option value="SANDBOX" <?php echo ($g['directpay']['stage'] ?? '') === 'SANDBOX' ? 'selected' : ''; ?>>Sandbox</option>
                                </select>
                            </div>
                            <div class="form-group full">
                                <label>Secret Key</label>
                                <input type="password" name="directpay_secret_key" value="<?php echo htmlspecialchars($g['directpay']['secret_key']); ?>" style="<?php echo $inputStyle; ?>">
                            </div>
                            <div class="form-group">
                                <label>Surcharge (%)</label>
                                <input type="number" name="directpay_surcharge_percent" value="<?php echo htmlspecialchars((string)($g['directpay']['surcharge_percent'] ?? 0)); ?>" min="0" max="100" step="0.01" style="<?php echo $inputStyle; ?>">
                                <span class="form-help">Added to order total when customer picks this method</span>
                            </div>
                            <div class="form-group full">
                                <label>Callback URL</label>
                                <div class="callback-box"><?php echo htmlspecialchars($callbackExamples['directpay']['return']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- PayHere -->
                    <div class="gateway-panel">
                        <h3>
                            PayHere
                            <label class="toggle-switch" style="margin-left:auto;">
                                <input type="checkbox" name="payment_payhere_enabled" <?php echo !empty($g['payhere']['enabled']) ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </h3>
                        <p class="gateway-desc">Card payments via <a href="https://payhere.lk/" target="_blank" rel="noopener">payhere.lk</a></p>
                        <div class="gateway-grid">
                            <div class="form-group">
                                <label>Merchant ID</label>
                                <input type="text" name="payhere_merchant_id" value="<?php echo htmlspecialchars($g['payhere']['merchant_id']); ?>" style="<?php echo $inputStyle; ?>">
                            </div>
                            <div class="form-group">
                                <label>Sandbox Mode</label>
                                <label class="toggle-switch" style="margin-top:8px;">
                                    <input type="checkbox" name="payhere_sandbox" <?php echo !empty($g['payhere']['sandbox']) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="form-group full">
                                <label>Merchant Secret</label>
                                <input type="password" name="payhere_merchant_secret" value="<?php echo htmlspecialchars($g['payhere']['merchant_secret']); ?>" style="<?php echo $inputStyle; ?>">
                            </div>
                            <div class="form-group">
                                <label>Surcharge (%)</label>
                                <input type="number" name="payhere_surcharge_percent" value="<?php echo htmlspecialchars((string)($g['payhere']['surcharge_percent'] ?? 0)); ?>" min="0" max="100" step="0.01" style="<?php echo $inputStyle; ?>">
                            </div>
                            <div class="form-group full">
                                <label>Notify URL (server callback)</label>
                                <div class="callback-box"><?php echo htmlspecialchars($callbackExamples['payhere']['notify']); ?></div>
                                <span class="form-help">Add this URL in your PayHere merchant dashboard.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payzy -->
                    <div class="gateway-panel">
                        <h3>
                            Payzy
                            <label class="toggle-switch" style="margin-left:auto;">
                                <input type="checkbox" name="payment_payzy_enabled" <?php echo !empty($g['payzy']['enabled']) ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </h3>
                        <p class="gateway-desc">Buy Now Pay Later via <a href="https://payzy.lk/" target="_blank" rel="noopener">payzy.lk</a> — see <a href="https://payzy.lk/dev-doc" target="_blank" rel="noopener">developer docs</a></p>
                        <div class="gateway-grid">
                            <div class="form-group">
                                <label>Merchant ID</label>
                                <input type="text" name="payzy_merchant_id" value="<?php echo htmlspecialchars($g['payzy']['merchant_id']); ?>" style="<?php echo $inputStyle; ?>">
                            </div>
                            <div class="form-group">
                                <label>Sandbox Mode</label>
                                <label class="toggle-switch" style="margin-top:8px;">
                                    <input type="checkbox" name="payzy_sandbox" <?php echo !empty($g['payzy']['sandbox']) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label>API Key</label>
                                <input type="password" name="payzy_api_key" value="<?php echo htmlspecialchars($g['payzy']['api_key']); ?>" style="<?php echo $inputStyle; ?>">
                            </div>
                            <div class="form-group">
                                <label>API Secret</label>
                                <input type="password" name="payzy_api_secret" value="<?php echo htmlspecialchars($g['payzy']['api_secret']); ?>" style="<?php echo $inputStyle; ?>">
                            </div>
                            <div class="form-group">
                                <label>API Base URL</label>
                                <input type="text" name="payzy_api_base_url" value="<?php echo htmlspecialchars($g['payzy']['api_base_url']); ?>" style="<?php echo $inputStyle; ?>">
                            </div>
                            <div class="form-group">
                                <label>Checkout API Path</label>
                                <input type="text" name="payzy_checkout_path" value="<?php echo htmlspecialchars($g['payzy']['checkout_path']); ?>" style="<?php echo $inputStyle; ?>">
                                <span class="form-help">From Payzy merchant docs (e.g. /api/v1/checkout/session)</span>
                            </div>
                            <div class="form-group">
                                <label>Surcharge (%)</label>
                                <input type="number" name="payzy_surcharge_percent" value="<?php echo htmlspecialchars((string)($g['payzy']['surcharge_percent'] ?? 0)); ?>" min="0" max="100" step="0.01" style="<?php echo $inputStyle; ?>">
                            </div>
                            <div class="form-group full">
                                <label>Notify URL</label>
                                <div class="callback-box"><?php echo htmlspecialchars($callbackExamples['payzy']['notify']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- PayKoko -->
                    <div class="gateway-panel">
                        <h3>
                            PayKoko
                            <label class="toggle-switch" style="margin-left:auto;">
                                <input type="checkbox" name="payment_paykoko_enabled" <?php echo !empty($g['paykoko']['enabled']) ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </h3>
                        <p class="gateway-desc">BNPL via <a href="https://paykoko.com/" target="_blank" rel="noopener">paykoko.com</a> — sandbox: qaapi.paykoko.com, live: prodapi.paykoko.com</p>
                        <div class="gateway-grid">
                            <div class="form-group">
                                <label>Merchant ID</label>
                                <input type="text" name="paykoko_merchant_id" value="<?php echo htmlspecialchars($g['paykoko']['merchant_id']); ?>" style="<?php echo $inputStyle; ?>">
                            </div>
                            <div class="form-group">
                                <label>Sandbox Mode</label>
                                <label class="toggle-switch" style="margin-top:8px;">
                                    <input type="checkbox" name="paykoko_sandbox" <?php echo !empty($g['paykoko']['sandbox']) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label>API Key</label>
                                <input type="password" name="paykoko_api_key" value="<?php echo htmlspecialchars($g['paykoko']['api_key']); ?>" style="<?php echo $inputStyle; ?>">
                            </div>
                            <div class="form-group">
                                <label>Checkout API Path</label>
                                <input type="text" name="paykoko_checkout_path" value="<?php echo htmlspecialchars($g['paykoko']['checkout_path']); ?>" style="<?php echo $inputStyle; ?>">
                            </div>
                            <div class="form-group">
                                <label>Surcharge (%)</label>
                                <input type="number" name="paykoko_surcharge_percent" value="<?php echo htmlspecialchars((string)($g['paykoko']['surcharge_percent'] ?? 13)); ?>" min="0" max="100" step="0.01" style="<?php echo $inputStyle; ?>">
                                <span class="form-help">Default 13% for Koko BNPL commission</span>
                            </div>
                            <div class="form-group full">
                                <label>API Base URL (optional)</label>
                                <input type="text" name="paykoko_api_base_url" value="<?php echo htmlspecialchars($settings['gateways']['paykoko']['api_base_url']); ?>" placeholder="Leave empty to use sandbox/live default" style="<?php echo $inputStyle; ?>">
                            </div>
                            <div class="form-group full">
                                <label>Public Key (for callback verification)</label>
                                <textarea name="paykoko_public_key" rows="3" style="<?php echo $inputStyle; ?>"><?php echo htmlspecialchars($g['paykoko']['public_key']); ?></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Private Key (optional)</label>
                                <textarea name="paykoko_private_key" rows="3" style="<?php echo $inputStyle; ?>"><?php echo htmlspecialchars($g['paykoko']['private_key']); ?></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Response / Notify URL</label>
                                <div class="callback-box"><?php echo htmlspecialchars($callbackExamples['paykoko']['notify']); ?></div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" style="margin-top:10px; padding:13px 30px; background:#001f52; color:#fff; border:none; border-radius:10px; font-family:'Poppins',sans-serif; font-size:14px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
                        <i class="fas fa-save"></i> Save All Payment Settings
                    </button>
                </form>

                <div class="admin-footer" style="margin-top:40px;">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo ADMIN_BRAND; ?>. All rights reserved.</p>
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
