<?php
$currentPage = 'invoice-template';
require_once __DIR__ . '/../config/app.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
$message = '';
$messageType = '';
$settings = lumak_invoice_get_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkboxes = [
        'enabled', 'auto_send_order_placed', 'auto_send_payment_confirmed',
        'auto_send_processing', 'auto_send_shipped', 'auto_send_paid', 'show_logo',
    ];
    foreach ($checkboxes as $key) {
        $settings[$key] = isset($_POST[$key]) ? '1' : '0';
    }

    $textFields = [
        'invoice_title', 'invoice_prefix', 'accent_color', 'secondary_color',
        'company_name', 'company_tagline', 'company_address', 'company_phone',
        'company_email', 'company_website', 'tax_label', 'tax_rate',
        'footer_note', 'payment_note', 'terms_text', 'email_subject', 'email_message',
        'paid_seal_position', 'paid_seal_offset_x', 'paid_seal_offset_y', 'paid_seal_rotation',
    ];
    foreach ($textFields as $key) {
        if (isset($_POST[$key])) {
            $settings[$key] = trim($_POST[$key]);
        }
    }

    lumak_invoice_save_settings($settings);
    $message = 'Invoice template saved successfully!';
    $messageType = 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Invoice Template - Admin | ARIGATO.LK</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .invoice-layout { display:grid; grid-template-columns:minmax(0,1fr) minmax(360px,44%); gap:22px; align-items:start; }
        .content-card { background:#fff; border:1px solid #eee; border-radius:14px; padding:24px; margin-bottom:18px; }
        .content-card h3 { font-size:15px; font-weight:700; color:#0d1137; margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; gap:8px; }
        .content-card h3 i { color:#D4A017; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .form-group { margin-bottom:14px; }
        .form-group label { display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="number"], .form-group input[type="color"], .form-group textarea, .form-group select {
            width:100%; padding:10px 12px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;
        }
        .form-group textarea { min-height:88px; resize:vertical; }
        .form-group small { display:block; font-size:11px; color:#6b7099; margin-top:4px; }
        .toggle-row { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f5f5f5; }
        .toggle-row:last-child { border-bottom:none; }
        .toggle-row span { font-size:13px; color:#333; }
        .switch { position:relative; width:44px; height:24px; }
        .switch input { opacity:0; width:0; height:0; }
        .slider { position:absolute; cursor:pointer; inset:0; background:#d1d5db; border-radius:999px; transition:.2s; }
        .slider:before { content:""; position:absolute; height:18px; width:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.2s; }
        .switch input:checked + .slider { background:#D4A017; }
        .switch input:checked + .slider:before { transform:translateX(20px); }
        .preview-panel { position:sticky; top:20px; }
        .preview-frame-wrap { background:#eef1f8; border:1px solid #e4e8f0; border-radius:14px; overflow:hidden; }
        .preview-toolbar { display:flex; justify-content:space-between; align-items:center; padding:12px 14px; background:#fff; border-bottom:1px solid #eee; }
        .preview-toolbar h4 { font-size:13px; font-weight:700; color:#0d1137; }
        .preview-frame { width:100%; height:720px; border:0; background:#eef1f8; }
        .btn-preview { padding:8px 14px; border:none; border-radius:8px; background:#FFE8A8; color:#D4A017; font-size:12px; font-weight:600; cursor:pointer; font-family:'Poppins',sans-serif; }
        .btn-preview:hover { background:#D4A017; color:#fff; }
        .token-hint { font-size:11px; color:#6b7099; margin-top:6px; }
        .save-bar { display:flex; gap:10px; justify-content:flex-end; margin-top:8px; }
        .range-row { display:grid; grid-template-columns:1fr auto; gap:10px; align-items:center; margin-bottom:12px; }
        .range-row input[type=range] { width:100%; accent-color:#D4A017; }
        .range-value { min-width:42px; text-align:right; font-size:12px; font-weight:700; color:#D4A017; }
        @media (max-width:1100px) { .invoice-layout { grid-template-columns:1fr; } .preview-panel { position:static; } }
        @media (max-width:768px) { .form-grid { grid-template-columns:1fr; } }
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
                        <h1 class="page-title">Invoice Template</h1>
                        <p class="page-subtitle">Customize the professional PDF invoice emailed to customers</p>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="admin-toast <?php echo $messageType; ?>">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <form method="POST" id="invoiceForm">
                    <div class="invoice-layout">
                        <div>
                            <div class="content-card">
                                <h3><i class="fas fa-power-off"></i> Invoice &amp; Auto-Send</h3>
                                <div class="toggle-row">
                                    <span>Enable invoice emails</span>
                                    <label class="switch"><input type="checkbox" name="enabled" value="1" <?php echo ($settings['enabled'] ?? '1') === '1' ? 'checked' : ''; ?>><span class="slider"></span></label>
                                </div>
                                <div class="toggle-row"><span>Auto-send when order is placed (COD / WhatsApp)</span><label class="switch"><input type="checkbox" name="auto_send_order_placed" value="1" <?php echo ($settings['auto_send_order_placed'] ?? '1') === '1' ? 'checked' : ''; ?>><span class="slider"></span></label></div>
                                <div class="toggle-row"><span>Auto-send when card payment is confirmed</span><label class="switch"><input type="checkbox" name="auto_send_payment_confirmed" value="1" <?php echo ($settings['auto_send_payment_confirmed'] ?? '1') === '1' ? 'checked' : ''; ?>><span class="slider"></span></label></div>
                                <div class="toggle-row"><span>Auto-send when order status → Processing</span><label class="switch"><input type="checkbox" name="auto_send_processing" value="1" <?php echo ($settings['auto_send_processing'] ?? '0') === '1' ? 'checked' : ''; ?>><span class="slider"></span></label></div>
                                <div class="toggle-row"><span>Auto-send when order status → Shipped</span><label class="switch"><input type="checkbox" name="auto_send_shipped" value="1" <?php echo ($settings['auto_send_shipped'] ?? '1') === '1' ? 'checked' : ''; ?>><span class="slider"></span></label></div>
                                <div class="toggle-row"><span>Auto-send when payment marked as Paid <small style="color:#16a34a;">(includes PAID seal)</small></span><label class="switch"><input type="checkbox" name="auto_send_paid" value="1" <?php echo ($settings['auto_send_paid'] ?? '1') === '1' ? 'checked' : ''; ?>><span class="slider"></span></label></div>
                            </div>

                            <div class="content-card">
                                <h3><i class="fas fa-palette"></i> Branding &amp; Design</h3>
                                <div class="form-grid">
                                    <div class="form-group"><label>Invoice Title</label><input type="text" name="invoice_title" value="<?php echo htmlspecialchars($settings['invoice_title']); ?>"></div>
                                    <div class="form-group"><label>Invoice Number Prefix</label><input type="text" name="invoice_prefix" value="<?php echo htmlspecialchars($settings['invoice_prefix']); ?>"><small>e.g. INV-AB260701A1B2</small></div>
                                    <div class="form-group"><label>Accent Color</label><input type="color" name="accent_color" value="<?php echo htmlspecialchars($settings['accent_color']); ?>"></div>
                                    <div class="form-group"><label>Secondary Color</label><input type="color" name="secondary_color" value="<?php echo htmlspecialchars($settings['secondary_color']); ?>"></div>
                                </div>
                                <div class="toggle-row" style="margin-top:8px;">
                                    <span>Show logo on invoice</span>
                                    <label class="switch"><input type="checkbox" name="show_logo" value="1" <?php echo ($settings['show_logo'] ?? '1') === '1' ? 'checked' : ''; ?>><span class="slider"></span></label>
                                </div>
                            </div>

                            <div class="content-card">
                                <h3><i class="fas fa-certificate" style="color:#16a34a;"></i> PAID Seal Position</h3>
                                <p style="font-size:12px;color:#6b7099;margin-bottom:16px;">Shown on invoices when payment status is <strong>Paid</strong>. Adjust position, fine-tune with offsets, and preview live on the right.</p>
                                <div class="form-group">
                                    <label>Position</label>
                                    <select name="paid_seal_position" onchange="onSealPositionChange()">
                                        <?php foreach (lumak_invoice_paid_seal_positions() as $value => $label): ?>
                                        <option value="<?php echo htmlspecialchars($value); ?>" <?php echo ($settings['paid_seal_position'] ?? 'over_totals') === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="range-row">
                                    <div>
                                        <label style="display:block;font-size:12px;font-weight:600;color:#333;margin-bottom:6px;">Horizontal offset</label>
                                        <input type="range" name="paid_seal_offset_x" min="-80" max="80" step="1" value="<?php echo htmlspecialchars($settings['paid_seal_offset_x'] ?? '0'); ?>" oninput="onSealControlChange(this)">
                                        <small>Negative = left, positive = right</small>
                                    </div>
                                    <span class="range-value" data-for="paid_seal_offset_x"><?php echo htmlspecialchars($settings['paid_seal_offset_x'] ?? '0'); ?></span>
                                </div>
                                <div class="range-row">
                                    <div>
                                        <label style="display:block;font-size:12px;font-weight:600;color:#333;margin-bottom:6px;">Vertical offset</label>
                                        <input type="range" name="paid_seal_offset_y" min="-80" max="80" step="1" value="<?php echo htmlspecialchars($settings['paid_seal_offset_y'] ?? '0'); ?>" oninput="onSealControlChange(this)">
                                        <small>Negative = up, positive = down</small>
                                    </div>
                                    <span class="range-value" data-for="paid_seal_offset_y"><?php echo htmlspecialchars($settings['paid_seal_offset_y'] ?? '0'); ?></span>
                                </div>
                                <div class="range-row" style="margin-bottom:0;">
                                    <div>
                                        <label style="display:block;font-size:12px;font-weight:600;color:#333;margin-bottom:6px;">Rotation (degrees)</label>
                                        <input type="range" name="paid_seal_rotation" min="-45" max="45" step="1" value="<?php echo htmlspecialchars($settings['paid_seal_rotation'] ?? '-14'); ?>" oninput="onSealControlChange(this)">
                                    </div>
                                    <span class="range-value" data-for="paid_seal_rotation"><?php echo htmlspecialchars($settings['paid_seal_rotation'] ?? '-14'); ?></span>
                                </div>
                            </div>

                            <div class="content-card">
                                <h3><i class="fas fa-building"></i> Company Details</h3>
                                <div class="form-grid">
                                    <div class="form-group"><label>Company Name</label><input type="text" name="company_name" value="<?php echo htmlspecialchars($settings['company_name']); ?>"></div>
                                    <div class="form-group"><label>Tagline</label><input type="text" name="company_tagline" value="<?php echo htmlspecialchars($settings['company_tagline']); ?>"></div>
                                    <div class="form-group"><label>Phone</label><input type="text" name="company_phone" value="<?php echo htmlspecialchars($settings['company_phone']); ?>"></div>
                                    <div class="form-group"><label>Email</label><input type="email" name="company_email" value="<?php echo htmlspecialchars($settings['company_email']); ?>"></div>
                                </div>
                                <div class="form-group"><label>Address</label><input type="text" name="company_address" value="<?php echo htmlspecialchars($settings['company_address']); ?>"></div>
                                <div class="form-group" style="margin-bottom:0;"><label>Website</label><input type="text" name="company_website" value="<?php echo htmlspecialchars($settings['company_website']); ?>"></div>
                            </div>

                            <div class="content-card">
                                <h3><i class="fas fa-file-invoice"></i> Invoice Notes</h3>
                                <div class="form-grid">
                                    <div class="form-group"><label>Tax Label</label><input type="text" name="tax_label" value="<?php echo htmlspecialchars($settings['tax_label']); ?>"></div>
                                    <div class="form-group"><label>Tax Rate (%)</label><input type="number" step="0.01" min="0" name="tax_rate" value="<?php echo htmlspecialchars($settings['tax_rate']); ?>"></div>
                                </div>
                                <div class="form-group"><label>Payment Note</label><textarea name="payment_note"><?php echo htmlspecialchars($settings['payment_note']); ?></textarea></div>
                                <div class="form-group"><label>Terms &amp; Conditions</label><textarea name="terms_text"><?php echo htmlspecialchars($settings['terms_text']); ?></textarea></div>
                                <div class="form-group" style="margin-bottom:0;"><label>Footer Message</label><textarea name="footer_note"><?php echo htmlspecialchars($settings['footer_note']); ?></textarea></div>
                            </div>

                            <div class="content-card">
                                <h3><i class="fas fa-envelope"></i> Email Settings</h3>
                                <div class="form-group"><label>Email Subject</label><input type="text" name="email_subject" value="<?php echo htmlspecialchars($settings['email_subject']); ?>"><div class="token-hint">Tokens: {invoice_number}, {order_number}, {customer_name}, {total}</div></div>
                                <div class="form-group" style="margin-bottom:0;"><label>Email Message</label><textarea name="email_message"><?php echo htmlspecialchars($settings['email_message']); ?></textarea><div class="token-hint">Tokens: {customer_name}, {order_number}, {invoice_number}, {company_name}</div></div>
                            </div>

                            <div class="save-bar">
                                <button type="button" class="btn-preview" onclick="refreshPreview()"><i class="fas fa-sync-alt"></i> Refresh Preview</button>
                                <button type="submit" class="btn-primary" style="padding:12px 28px;"><i class="fas fa-save"></i> Save Template</button>
                            </div>
                        </div>

                        <div class="preview-panel">
                            <div class="preview-frame-wrap">
                                <div class="preview-toolbar">
                                    <h4><i class="fas fa-eye" style="color:#D4A017;"></i> Live Preview</h4>
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <label style="font-size:12px;color:#555;display:flex;align-items:center;gap:6px;cursor:pointer;">
                                            <input type="checkbox" name="preview_paid" value="1" form="invoiceForm" onchange="refreshPreview()"> Preview PAID seal
                                        </label>
                                        <button type="button" class="btn-preview" onclick="refreshPreview()">Update</button>
                                    </div>
                                </div>
                                <iframe id="invoicePreview" class="preview-frame" src="<?php echo BASE_URL; ?>admin/invoice-preview.php"></iframe>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="admin-footer">
                    <p>&copy; <?php echo date('Y'); ?> ARIGATO.LK. All rights reserved.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.querySelector('.admin-layout').classList.toggle('sidebar-collapsed');
    });

    function getPreviewScrollTop(iframe) {
        try {
            var doc = iframe.contentDocument || iframe.contentWindow.document;
            return doc.documentElement.scrollTop || doc.body.scrollTop || iframe.contentWindow.scrollY || 0;
        } catch (e) {
            return 0;
        }
    }

    function setPreviewScrollTop(iframe, scrollTop) {
        try {
            var win = iframe.contentWindow;
            var doc = iframe.contentDocument || win.document;
            doc.documentElement.scrollTop = scrollTop;
            doc.body.scrollTop = scrollTop;
            win.scrollTo(0, scrollTop);
        } catch (e) {}
    }

    function buildSealStyle() {
        var form = document.getElementById('invoiceForm');
        var position = form.paid_seal_position.value;
        var offsetX = parseInt(form.paid_seal_offset_x.value, 10) || 0;
        var offsetY = parseInt(form.paid_seal_offset_y.value, 10) || 0;
        var rotation = parseFloat(form.paid_seal_rotation.value) || -14;
        var styles = ['position:absolute', 'z-index:3', 'pointer-events:none', 'opacity:.9'];

        switch (position) {
            case 'top_right':
                styles.push('top:' + (8 + offsetY) + 'px', 'right:' + (-offsetX) + 'px', 'transform:rotate(' + rotation + 'deg)');
                break;
            case 'top_left':
                styles.push('top:' + (8 + offsetY) + 'px', 'left:' + offsetX + 'px', 'transform:rotate(' + rotation + 'deg)');
                break;
            case 'center':
                styles.push('top:calc(50% + ' + offsetY + 'px)', 'left:calc(50% + ' + offsetX + 'px)', 'transform:translate(-50%,-50%) rotate(' + rotation + 'deg)');
                break;
            case 'bottom_right':
                styles.push('bottom:' + (80 - offsetY) + 'px', 'right:' + (-offsetX) + 'px', 'transform:rotate(' + rotation + 'deg)');
                break;
            case 'bottom_left':
                styles.push('bottom:' + (80 - offsetY) + 'px', 'left:' + offsetX + 'px', 'transform:rotate(' + rotation + 'deg)');
                break;
            default:
                styles.push('top:' + (-28 + offsetY) + 'px', 'right:' + (8 - offsetX) + 'px', 'transform:rotate(' + rotation + 'deg)');
                break;
        }
        return styles.join(';');
    }

    function updateSealInPreview() {
        var iframe = document.getElementById('invoicePreview');
        var form = document.getElementById('invoiceForm');
        if (!form.preview_paid || !form.preview_paid.checked) {
            return false;
        }
        try {
            var seal = iframe.contentDocument.querySelector('.paid-seal');
            if (seal) {
                seal.style.cssText = buildSealStyle();
                return true;
            }
        } catch (e) {}
        return false;
    }

    function onSealControlChange(input) {
        updateRangeLabel(input);
        if (!updateSealInPreview()) {
            schedulePreviewRefresh();
        }
    }

    function onSealPositionChange() {
        if (!updateSealInPreview()) {
            schedulePreviewRefresh();
        }
    }

    var previewTimer;
    function schedulePreviewRefresh(delay) {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(refreshPreview, delay || 300);
    }

    function refreshPreview() {
        var form = document.getElementById('invoiceForm');
        var iframe = document.getElementById('invoicePreview');
        var scrollTop = getPreviewScrollTop(iframe);
        var data = new FormData(form);

        fetch('<?php echo BASE_URL; ?>admin/invoice-preview.php', {
            method: 'POST',
            body: data
        }).then(function(r) { return r.text(); }).then(function(html) {
            iframe.onload = function() {
                setPreviewScrollTop(iframe, scrollTop);
                iframe.onload = null;
            };
            iframe.srcdoc = html;
            setTimeout(function() { setPreviewScrollTop(iframe, scrollTop); }, 30);
            setTimeout(function() { setPreviewScrollTop(iframe, scrollTop); }, 120);
        });
    }

    function updateRangeLabel(input) {
        var label = document.querySelector('.range-value[data-for="' + input.name + '"]');
        if (label) label.textContent = input.value;
    }

    document.querySelectorAll('input[type=range]').forEach(function(input) {
        updateRangeLabel(input);
    });

    document.getElementById('invoiceForm').addEventListener('input', function(e) {
        if (e.target.type === 'range' || e.target.name === 'paid_seal_position') {
            return;
        }
        schedulePreviewRefresh(400);
    });
    </script>
</body>
</html>
