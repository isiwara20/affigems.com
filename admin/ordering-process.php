<?php
$currentPage = 'ordering-process';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
require_once __DIR__ . '/../dal/ShippingRatesDAL.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$settingsDAL = new SiteSettingsDAL();
$shippingDAL = new ShippingRatesDAL();
$adminName = $_SESSION['user_name'] ?? 'Admin';
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingsDAL->set('checkout_place_order_enabled', isset($_POST['place_order_enabled']) ? '1' : '0');
    $settingsDAL->set('checkout_whatsapp_order_enabled', isset($_POST['whatsapp_order_enabled']) ? '1' : '0');
    $settingsDAL->set('delivery_fee', trim($_POST['delivery_fee'] ?? '250'));
    $settingsDAL->set('free_delivery_threshold', trim($_POST['free_delivery_threshold'] ?? '2500'));

    $countries = [];
    $codes = $_POST['rate_code'] ?? [];
    $names = $_POST['rate_name'] ?? [];
    $flags = $_POST['rate_flag'] ?? [];
    $fees = $_POST['rate_fee'] ?? [];
    $enabled = $_POST['rate_enabled'] ?? [];

    foreach ($codes as $i => $code) {
        $code = strtoupper(trim($code));
        $name = trim($names[$i] ?? '');
        if ($code === '' || $name === '') {
            continue;
        }
        $countries[] = [
            'code' => $code,
            'name' => $name,
            'flag' => trim($flags[$i] ?? ''),
            'fee' => max(0, (int)($fees[$i] ?? 0)),
            'enabled' => in_array($code, $enabled, true),
        ];
    }

    $shippingDAL->saveConfig([
        'rest_of_world' => max(0, (int)($_POST['rest_of_world_fee'] ?? ShippingRatesDAL::DEFAULT_REST_OF_WORLD)),
        'countries' => $countries,
    ]);

    $message = 'Ordering process settings saved successfully!';
}

$placeOrderEnabled = $settingsDAL->get('checkout_place_order_enabled', '1');
$whatsappOrderEnabled = $settingsDAL->get('checkout_whatsapp_order_enabled', '1');
$deliveryFee = $settingsDAL->get('delivery_fee', '250');
$freeDeliveryThreshold = $settingsDAL->get('free_delivery_threshold', '2500');
$shippingConfig = $shippingDAL->getConfig();
$shippingCountries = $shippingConfig['countries'];
$restOfWorldFee = $shippingConfig['rest_of_world'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Ordering Process - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .ordering-card { background:#fff; border:1px solid #eee; border-radius:14px; padding:24px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; transition:all 0.2s; }
        .ordering-card:hover { border-color:#1a1f4e; }
        .ordering-card-left { display:flex; align-items:center; gap:16px; }
        .ordering-card-icon { width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .ordering-card-icon.order { background:#f0f4ff; color:#0d1137; }
        .ordering-card-icon.whatsapp { background:#ecfdf5; color:#25d366; }
        .ordering-card-info h3 { font-size:15px; font-weight:600; color:#0d1137; margin-bottom:2px; display:flex; align-items:center; gap:8px; }
        .ordering-card-info p { font-size:12px; color:#6b7099; margin:0; line-height:1.5; }
        .toggle-switch { position:relative; width:50px; height:26px; }
        .toggle-switch input { opacity:0; width:0; height:0; }
        .toggle-slider { position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background:#e4e8f0; border-radius:26px; transition:0.3s; }
        .toggle-slider:before { content:''; position:absolute; height:20px; width:20px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:0.3s; box-shadow:0 1px 3px rgba(0,0,0,0.15); }
        .toggle-switch input:checked + .toggle-slider { background:#16a34a; }
        .toggle-switch input:checked + .toggle-slider:before { transform:translateX(24px); }
        .status-badge { font-size:11px; font-weight:600; padding:4px 10px; border-radius:20px; }
        .status-badge.enabled { background:#ecfdf5; color:#059669; }
        .status-badge.disabled { background:#fef2f2; color:#ef4444; }
        .ordering-note { background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:flex-start; gap:10px; font-size:12px; color:#92400e; line-height:1.6; }
        .ordering-note i { font-size:14px; margin-top:2px; flex-shrink:0; }
        .shipping-rates-card { background:#fff; border:1px solid #eee; border-radius:14px; padding:28px; margin-top:24px; margin-bottom:16px; }
        .shipping-rates-card h3 { font-size:15px; font-weight:700; color:#0d1137; margin-bottom:6px; display:flex; align-items:center; gap:8px; }
        .shipping-rates-card .card-desc { font-size:12px; color:#6b7099; margin-bottom:20px; }
        .shipping-rates-table { width:100%; border-collapse:collapse; }
        .shipping-rates-table th { text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:0.04em; color:#888; padding:10px 8px; border-bottom:1px solid #eef1f6; }
        .shipping-rates-table td { padding:10px 8px; border-bottom:1px solid #f5f5f8; vertical-align:middle; }
        .shipping-rates-table input[type="text"],
        .shipping-rates-table input[type="number"] { width:100%; padding:9px 10px; border:1.5px solid #e4e8f0; border-radius:8px; font-family:'Poppins',sans-serif; font-size:12px; outline:none; }
        .shipping-rates-table input[type="text"]:focus,
        .shipping-rates-table input[type="number"]:focus { border-color:#003b94; }
        .shipping-rates-table .col-flag { width:70px; }
        .shipping-rates-table .col-code { width:80px; }
        .shipping-rates-table .col-fee { width:120px; }
        .shipping-rates-table .col-enabled { width:70px; text-align:center; }
        .shipping-rates-table .col-remove { width:44px; text-align:center; }
        .btn-add-country { margin-top:12px; border:1px dashed #c4b5fd; background:#f8fafd; color:#002868; border-radius:10px; padding:8px 14px; font-size:12px; font-weight:600; cursor:pointer; font-family:'Poppins',sans-serif; }
        .btn-add-country:hover { background:#e8f0fe; }
        .btn-remove-rate { border:none; background:#fef2f2; color:#ef4444; width:32px; height:32px; border-radius:8px; cursor:pointer; }
        .rest-of-world-row { display:grid; grid-template-columns:1fr 200px; gap:16px; margin-top:20px; padding-top:20px; border-top:1px solid #f0f0f0; align-items:end; }
        @media (max-width:768px) {
            .shipping-rates-wrap { overflow-x:auto; }
            .shipping-rates-table { min-width:640px; }
            .rest-of-world-row { grid-template-columns:1fr; }
        }
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
                        <h1 class="page-title">Ordering Process</h1>
                        <p class="page-subtitle">Control how customers can place orders at checkout</p>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="admin-toast success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <div class="ordering-note">
                    <i class="fas fa-info-circle"></i>
                    <span>At least one ordering method must be enabled. If both are disabled, the "Place Order" button will remain visible as a fallback to prevent customers from being unable to checkout.</span>
                </div>

                <form method="POST">
                    <div class="ordering-card">
                        <div class="ordering-card-left">
                            <div class="ordering-card-icon order"><i class="fas fa-shopping-bag"></i></div>
                            <div class="ordering-card-info">
                                <h3>Place Order <span class="status-badge <?php echo $placeOrderEnabled === '1' ? 'enabled' : 'disabled'; ?>"><?php echo $placeOrderEnabled === '1' ? 'Active' : 'Disabled'; ?></span></h3>
                                <p>Standard order placement through the website. Customers fill in delivery details and submit the order directly. Supports Cash on Delivery and Card Payment.</p>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="place_order_enabled" <?php echo $placeOrderEnabled === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="ordering-card">
                        <div class="ordering-card-left">
                            <div class="ordering-card-icon whatsapp"><i class="fab fa-whatsapp"></i></div>
                            <div class="ordering-card-info">
                                <h3>Place Order with WhatsApp <span class="status-badge <?php echo $whatsappOrderEnabled === '1' ? 'enabled' : 'disabled'; ?>"><?php echo $whatsappOrderEnabled === '1' ? 'Active' : 'Disabled'; ?></span></h3>
                                <p>Allows customers to place orders via WhatsApp. The order details are sent as a message to your WhatsApp business number for manual processing.</p>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="whatsapp_order_enabled" <?php echo $whatsappOrderEnabled === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <!-- Sri Lanka Delivery Fee -->
                    <div style="background:#fff; border:1px solid #eee; border-radius:14px; padding:28px; margin-top:24px; margin-bottom:16px;">
                        <h3 style="font-size:15px; font-weight:700; color:#0d1137; margin-bottom:6px; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-truck" style="color:#e8600a;"></i> Sri Lanka Delivery
                        </h3>
                        <p style="font-size:12px; color:#6b7099; margin-bottom:20px;">Local delivery charges for orders shipped within Sri Lanka.</p>
                        
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div>
                                <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Delivery Fee (LKR)</label>
                                <input type="number" name="delivery_fee" value="<?php echo htmlspecialchars($deliveryFee); ?>" min="0" step="1" style="width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;" required>
                                <p style="font-size:11px; color:#6b7099; margin-top:4px;">Charged when order is below the free delivery threshold</p>
                            </div>
                            <div>
                                <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Free Delivery Above (LKR)</label>
                                <input type="number" name="free_delivery_threshold" value="<?php echo htmlspecialchars($freeDeliveryThreshold); ?>" min="0" step="1" style="width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;" required>
                                <p style="font-size:11px; color:#6b7099; margin-top:4px;">Sri Lanka orders above this amount get free delivery (0 = always charge)</p>
                            </div>
                        </div>
                    </div>

                    <!-- International Shipping Rates -->
                    <div class="shipping-rates-card">
                        <h3><i class="fas fa-globe-americas" style="color:#003b94;"></i> International Shipping Rates</h3>
                        <p class="card-desc">Set delivery charges per country for worldwide orders. Customers select their country at checkout and the matching fee is applied automatically.</p>

                        <div class="shipping-rates-wrap">
                            <table class="shipping-rates-table">
                                <thead>
                                    <tr>
                                        <th class="col-flag">Flag</th>
                                        <th>Country</th>
                                        <th class="col-code">Code</th>
                                        <th class="col-fee">Fee (LKR)</th>
                                        <th class="col-enabled">Active</th>
                                        <th class="col-remove"></th>
                                    </tr>
                                </thead>
                                <tbody id="shippingRatesBody">
                                    <?php foreach ($shippingCountries as $i => $country): ?>
                                    <tr>
                                        <td><input type="text" name="rate_flag[]" value="<?php echo htmlspecialchars($country['flag']); ?>" placeholder="🇺🇸" maxlength="8"></td>
                                        <td><input type="text" name="rate_name[]" value="<?php echo htmlspecialchars($country['name']); ?>" placeholder="United States" required></td>
                                        <td><input type="text" name="rate_code[]" value="<?php echo htmlspecialchars($country['code']); ?>" placeholder="US" maxlength="5" style="text-transform:uppercase;" required></td>
                                        <td><input type="number" name="rate_fee[]" value="<?php echo (int)$country['fee']; ?>" min="0" step="1" required></td>
                                        <td style="text-align:center;"><input type="checkbox" name="rate_enabled[]" value="<?php echo htmlspecialchars($country['code']); ?>" <?php echo !empty($country['enabled']) ? 'checked' : ''; ?>></td>
                                        <td><button type="button" class="btn-remove-rate" onclick="removeRateRow(this)" title="Remove"><i class="fas fa-trash"></i></button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn-add-country" id="addCountryRateBtn"><i class="fas fa-plus"></i> Add Country</button>

                        <div class="rest-of-world-row">
                            <div>
                                <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Rest of World Fee (LKR)</label>
                                <p style="font-size:11px; color:#6b7099; margin:0;">Applied when the customer's country is not Sri Lanka and not in the list above.</p>
                            </div>
                            <div>
                                <input type="number" name="rest_of_world_fee" value="<?php echo (int)$restOfWorldFee; ?>" min="0" step="1" style="width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;" required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" style="margin-top:10px; padding:13px 30px; background:#001f52; color:#fff; border:none; border-radius:10px; font-family:'Poppins',sans-serif; font-size:14px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
                        <i class="fas fa-save"></i> Save Settings
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

    var rateRowIndex = 0;

    document.getElementById('addCountryRateBtn').addEventListener('click', function() {
        var tbody = document.getElementById('shippingRatesBody');
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="text" name="rate_flag[]" placeholder="🇺🇸" maxlength="8"></td>' +
            '<td><input type="text" name="rate_name[]" placeholder="Country name" required></td>' +
            '<td><input type="text" name="rate_code[]" placeholder="US" maxlength="5" style="text-transform:uppercase;" required></td>' +
            '<td><input type="number" name="rate_fee[]" value="0" min="0" step="1" required></td>' +
            '<td style="text-align:center;"><input type="checkbox" name="rate_enabled[]" value="NEW" class="rate-enabled-new" checked></td>' +
            '<td><button type="button" class="btn-remove-rate" onclick="removeRateRow(this)" title="Remove"><i class="fas fa-trash"></i></button></td>';
        tbody.appendChild(tr);
        var codeInput = tr.querySelector('input[name="rate_code[]"]');
        var enabledBox = tr.querySelector('.rate-enabled-new');
        codeInput.addEventListener('input', function() {
            enabledBox.value = this.value.toUpperCase();
        });
    });

    function removeRateRow(btn) {
        var tbody = document.getElementById('shippingRatesBody');
        if (tbody.children.length <= 1) {
            alert('At least one country row is required.');
            return;
        }
        btn.closest('tr').remove();
    }
    </script>
</body>
</html>
