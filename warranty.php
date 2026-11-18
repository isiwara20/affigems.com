<?php
/**
 * Warranty check and claim page.
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/dal/WarrantyRegistryDAL.php';
require_once __DIR__ . '/dal/WarrantyClaimDAL.php';

$pageTitle = 'Check Warranty - ' . SITE_NAME;
$searched = false;
$reference = '';
$warrantyItems = [];
$searchError = '';

if (isset($_GET['reference']) && trim($_GET['reference']) !== '') {
    $searched = true;
    $reference = trim($_GET['reference']);
    try {
        $registryDAL = new WarrantyRegistryDAL();
        $normalized = ezymart_warranty_normalize_reference($reference);
        $warrantyItems = $registryDAL->getByInvoiceOrOrder($normalized);
        if (empty($warrantyItems)) {
            $single = $registryDAL->getBySerial(trim($reference));
            if ($single) {
                $warrantyItems = [$single];
            }
        }
    } catch (Throwable $e) {
        $searchError = 'Unable to search warranty records right now.';
    }
}

$claimDAL = null;
try {
    $claimDAL = new WarrantyClaimDAL();
} catch (Throwable $e) {
    $claimDAL = null;
}

require_once __DIR__ . '/views/partials/header.php';
?>

<style>
.warranty-hero {
    position: relative;
    padding: clamp(40px, 6vw, 56px) 0 clamp(48px, 7vw, 64px);
    background: #D49A0A;
    overflow: hidden;
    text-align: center;
    color: #fff;
}
.warranty-hero-inner { position: relative; z-index: 1; max-width: 620px; margin: 0 auto; }
.warranty-hero-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.22);
    padding: 8px 18px; border-radius: 999px; font-size: 11px; font-weight: 700;
    letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 16px;
}
.warranty-hero h1 { font-size: clamp(1.6rem, 4vw, 2.2rem); font-weight: 800; margin-bottom: 10px; }
.warranty-hero p { font-size: 14px; color: rgba(255,255,255,0.9); line-height: 1.65; margin: 0; }
.warranty-section { padding: 0 0 60px; margin-top: -28px; position: relative; z-index: 2; }
.warranty-container { max-width: 820px; margin: 0 auto; }
.warranty-search {
    background: #fff; border: 1px solid #F5E6B8; border-radius: 16px;
    padding: 28px 30px; margin-bottom: 24px; box-shadow: 0 8px 32px rgba(196, 138, 10, 0.1);
}
.warranty-search-form { display: flex; gap: 12px; }
.warranty-search-form input {
    flex: 1; padding: 15px 20px; border: 1.5px solid #F5E6B8; border-radius: 12px;
    font-size: 14px; font-family: 'Poppins', sans-serif; outline: none; background: #FFF8E6;
}
.warranty-search-form input:focus { border-color: #E5A91A; background: #fff; box-shadow: 0 0 0 3px rgba(229, 169, 26, 0.12); }
.warranty-search-form button {
    padding: 15px 28px; background: #D49A0A; color: #1a1a1a;
    border: none; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer;
    font-family: 'Poppins', sans-serif; white-space: nowrap;
}
.warranty-card {
    background: #fff; border: 1px solid #F5E6B8; border-radius: 16px;
    padding: 24px; margin-bottom: 16px; box-shadow: 0 4px 20px rgba(196, 138, 10, 0.06);
}
.warranty-card h3 { font-size: 16px; font-weight: 700; color: #D4A017; margin: 0 0 16px; }
.warranty-product-head {
    display: grid;
    grid-template-columns: 88px 1fr;
    gap: 14px;
    align-items: center;
    margin-bottom: 16px;
    padding: 12px;
    border: 1px solid #e7eefb;
    border-radius: 14px;
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}
.warranty-product-thumb {
    width: 88px;
    height: 88px;
    border-radius: 12px;
    border: 1px solid #dce7f9;
    object-fit: cover;
    background: #f5f8fd;
}
.warranty-product-placeholder {
    width: 88px;
    height: 88px;
    border-radius: 12px;
    border: 1px solid #dce7f9;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #7ea5e6;
    background: #f5f8fd;
    font-size: 28px;
}
.warranty-product-meta h3 { margin: 0 0 4px; }
.warranty-product-meta p {
    margin: 0;
    font-size: 12px;
    color: #5b6680;
}
.warranty-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 20px; }
.warranty-field label { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7099; font-weight: 700; margin-bottom: 4px; }
.warranty-field p { margin: 0; font-size: 14px; color: #1a1a2e; font-weight: 600; }
.warranty-status { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; }
.warranty-status.active { color: #16a34a; }
.warranty-status.expired { color: #ef4444; }
.warranty-claim-btn {
    margin-top: 18px; padding: 12px 22px; background: #D49A0A;
    color: #fff; border: none; border-radius: 10px; font-family: 'Poppins', sans-serif;
    font-size: 13px; font-weight: 600; cursor: pointer;
}
.warranty-active-claim-note {
    margin-top: 16px;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #fed7aa;
    background: #fff7ed;
    color: #9a3412;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.6;
}
.warranty-claim-panel {
    display: none; margin-top: 18px; padding-top: 18px; border-top: 1px solid #f0f2f8;
}
.warranty-claim-panel.open { display: block; }
.warranty-claim-panel .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
.warranty-claim-panel label { display: block; font-size: 12px; font-weight: 600; color: #333; margin-bottom: 6px; }
.warranty-claim-panel input,
.warranty-claim-panel select,
.warranty-claim-panel textarea {
    width: 100%; padding: 11px 14px; border: 1.5px solid #e4e8f0; border-radius: 10px;
    font-family: 'Poppins', sans-serif; font-size: 13px; outline: none;
}
.warranty-claim-panel textarea { resize: vertical; min-height: 90px; }
.warranty-submit-btn {
    padding: 12px 24px; background: #0d1137; color: #fff; border: none; border-radius: 10px;
    font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer;
}
.warranty-claim-form {
    position: relative;
}
.warranty-form-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.85);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    z-index: 5;
    flex-direction: column;
    padding: 18px;
}
.warranty-form-overlay[hidden] {
    display: none !important;
}
.warranty-form-overlay-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #0d1137;
}
.warranty-form-overlay i { color: #E5A91A; font-size: 18px; }
.warranty-form-overlay span { font-size: 13px; font-weight: 700; color: #D4A017; }
.warranty-msg { margin-top: 12px; font-size: 13px; }
.warranty-msg.success { color: #16a34a; }
.warranty-msg.error { color: #ef4444; }
.warranty-empty { text-align: center; padding: 36px 20px; color: #888; }
.warranty-success-overlay {
    position: fixed;
    inset: 0;
    background: rgba(13, 17, 55, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3000;
    padding: 20px;
}
.warranty-success-overlay[hidden] {
    display: none !important;
}
.warranty-success-card {
    width: min(540px, 100%);
    background: #fff;
    border-radius: 16px;
    border: 1px solid #dbe7ff;
    box-shadow: 0 18px 48px rgba(196, 138, 10, 0.28);
    padding: 24px 22px;
    text-align: center;
}
.warranty-success-icon {
    width: 58px;
    height: 58px;
    margin: 0 auto 14px;
    border-radius: 50%;
    background: #ecfdf5;
    color: #16a34a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}
.warranty-success-title {
    margin: 0 0 8px;
    font-size: 19px;
    font-weight: 800;
    color: #D4A017;
}
.warranty-success-text {
    margin: 0;
    font-size: 14px;
    color: #4b5563;
    line-height: 1.65;
}
.warranty-success-claim {
    margin-top: 12px;
    padding: 11px 14px;
    border-radius: 10px;
    background: #f5f8fd;
    border: 1px dashed #bfd4f7;
    font-size: 15px;
    font-weight: 700;
    color: #0d1137;
}
.warranty-success-close {
    margin-top: 16px;
    padding: 11px 22px;
    border: none;
    border-radius: 10px;
    background: #D49A0A;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
}
@media (max-width: 768px) {
    .warranty-search-form { flex-direction: column; }
    .warranty-grid, .warranty-claim-panel .form-row { grid-template-columns: 1fr; }
    .warranty-product-head { grid-template-columns: 1fr; text-align: center; justify-items: center; }
}
</style>

<div class="product-page-breadcrumb">
    <div class="container">
        <a href="<?php echo BASE_URL; ?>">Home</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <span>Check Warranty</span>
    </div>
</div>

<header class="warranty-hero">
    <div class="container">
        <div class="warranty-hero-inner">
            <span class="warranty-hero-badge"><i class="fas fa-shield-alt"></i> Warranty Support</span>
            <h1>ARIGATO – Check Your Warranty Status</h1>
            <p>Check the warranty details of your purchased product here.</p>
        </div>
    </div>
</header>

<section class="warranty-section">
    <div class="container">
        <div class="warranty-container">
            <div class="warranty-search">
                <form method="GET" class="warranty-search-form">
                    <input type="text" name="reference" placeholder="Enter Invoice Number / Order Number" value="<?php echo htmlspecialchars($reference); ?>" required>
                    <button type="submit"><i class="fas fa-search"></i> Check Warranty</button>
                </form>
            </div>

            <?php if (!$searched): ?>
            <div class="warranty-card warranty-empty">
                <i class="fas fa-shield-alt" style="font-size:2rem;color:#E5A91A;margin-bottom:12px;"></i>
                <p>Enter your invoice number (e.g. INV-AB260708A1B2) or the product serial number printed on your invoice to view warranty details.</p>
            </div>
            <?php elseif ($searchError): ?>
            <div class="warranty-card warranty-empty"><p><?php echo htmlspecialchars($searchError); ?></p></div>
            <?php elseif (empty($warrantyItems)): ?>
            <div class="warranty-card warranty-empty">
                <h3 style="color:#D4A017;">No warranty record found</h3>
                <p>Please check your invoice or serial number and try again.</p>
            </div>
            <?php else: ?>
                <?php if (count($warrantyItems) > 1): ?>
                <div class="warranty-card" style="padding:14px 18px;">
                    <p style="margin:0;font-size:13px;color:#4b5563;">
                        <i class="fas fa-info-circle" style="color:#E5A91A;margin-right:6px;"></i>
                        This invoice contains multiple products. Please choose the relevant product and submit the claim for that item.
                    </p>
                </div>
                <?php endif; ?>
                <?php foreach ($warrantyItems as $item):
                    $isActive = ezymart_warranty_is_active($item['expires_at']);
                    $panelId = 'claim-panel-' . (int)$item['id'];
                    $productImage = !empty($item['product_image']) ? media_image_url($item['product_image']) : '';
                    $hasOpenClaim = $claimDAL ? $claimDAL->hasOpenClaimForRegistry((int)$item['id']) : false;
                ?>
                <div class="warranty-card" id="warranty-item-<?php echo (int)$item['id']; ?>">
                    <div class="warranty-product-head">
                        <?php if ($productImage): ?>
                            <img src="<?php echo htmlspecialchars($productImage); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="warranty-product-thumb" loading="lazy">
                        <?php else: ?>
                            <div class="warranty-product-placeholder"><i class="fas fa-box-open"></i></div>
                        <?php endif; ?>
                        <div class="warranty-product-meta">
                            <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                            <p>Claim this exact purchased item only (Serial-based warranty check).</p>
                        </div>
                    </div>
                    <div class="warranty-grid">
                        <div class="warranty-field">
                            <label>Product Name</label>
                            <p><?php echo htmlspecialchars($item['product_name']); ?></p>
                        </div>
                        <div class="warranty-field">
                            <label>Serial Number</label>
                            <p><?php echo htmlspecialchars($item['serial_number']); ?></p>
                        </div>
                        <div class="warranty-field">
                            <label>Purchase Date</label>
                            <p><?php echo ezymart_warranty_format_date($item['purchase_date']); ?></p>
                        </div>
                        <div class="warranty-field">
                            <label>Warranty Period</label>
                            <p><?php echo htmlspecialchars(ezymart_warranty_period_label($item['warranty_period'])); ?></p>
                        </div>
                        <div class="warranty-field">
                            <label>Warranty Expiry Date</label>
                            <p><?php echo ezymart_warranty_format_date($item['expires_at']); ?></p>
                        </div>
                        <div class="warranty-field">
                            <label>Status</label>
                            <p class="warranty-status <?php echo $isActive ? 'active' : 'expired'; ?>">
                                <?php if ($isActive): ?>
                                    <i class="fas fa-check-circle"></i> Active
                                <?php else: ?>
                                    <i class="fas fa-times-circle"></i> Expired
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($isActive && !$hasOpenClaim): ?>
                    <button type="button" class="warranty-claim-btn" onclick="toggleClaimPanel('<?php echo $panelId; ?>')">
                        <i class="fas fa-tools"></i> Request Warranty / Claim Warranty
                    </button>

                    <div class="warranty-claim-panel" id="<?php echo $panelId; ?>">
                        <form class="warranty-claim-form" data-ajax enctype="multipart/form-data">
                            <input type="hidden" name="registry_id" value="<?php echo (int)$item['id']; ?>">
                            <input type="hidden" name="invoice_number" value="<?php echo htmlspecialchars($item['invoice_number']); ?>">
                            <input type="hidden" name="invoice_date" value="<?php echo htmlspecialchars($item['purchase_date']); ?>">
                            <div class="warranty-form-overlay" hidden aria-hidden="true">
                                <div class="warranty-form-overlay-inner">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Submitting claim...</span>
                                </div>
                            </div>

                            <div class="form-row">
                                <div>
                                    <label>Issue Type *</label>
                                    <input type="text" name="issue_type" placeholder="Display issue, not powering on..." required>
                                </div>
                                <div>
                                    <label>Customer Name *</label>
                                    <input type="text" name="customer_name" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div style="margin-bottom:12px;">
                                <label>Details / Description *</label>
                                <textarea name="issue_description" placeholder="Describe the product issue..." required></textarea>
                            </div>

                            <div class="form-row">
                                <div>
                                    <label>Invoice Number</label>
                                    <input type="text" value="<?php echo htmlspecialchars($item['invoice_number']); ?>" readonly>
                                </div>
                                <div>
                                    <label>Invoice Date</label>
                                    <input type="text" value="<?php echo ezymart_warranty_format_date($item['purchase_date']); ?>" readonly>
                                </div>
                            </div>

                            <div class="form-row">
                                <div>
                                    <label>Email Address</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" placeholder="you@example.com">
                                </div>
                                <div>
                                    <label>Mobile Number *</label>
                                    <input type="text" name="phone" value="<?php echo htmlspecialchars($_SESSION['user_phone'] ?? ''); ?>" placeholder="07X XXX XXXX" required>
                                </div>
                            </div>

                            <div style="margin-bottom:12px;">
                                <label>Attachment (photo or short video)</label>
                                <input type="file" name="attachment" accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime">
                            </div>

                            <button type="submit" class="warranty-submit-btn"><i class="fas fa-paper-plane"></i> Submit Claim</button>
                            <div class="warranty-msg"></div>
                        </form>
                    </div>
                    <?php elseif ($isActive && $hasOpenClaim): ?>
                    <div class="warranty-active-claim-note">
                        <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>
                        A warranty claim is already active for this product. You can submit another claim only if the previous one is rejected.
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<div id="warrantySuccessOverlay" class="warranty-success-overlay" hidden aria-hidden="true">
    <div class="warranty-success-card" role="dialog" aria-modal="true" aria-labelledby="warrantySuccessTitle">
        <div class="warranty-success-icon"><i class="fas fa-check"></i></div>
        <h3 id="warrantySuccessTitle" class="warranty-success-title">Warranty Claim Submitted</h3>
        <p class="warranty-success-text">Your warranty claim has been submitted successfully.</p>
        <div class="warranty-success-claim">Claim Number: <span id="warrantySuccessClaimNo">-</span></div>
        <button type="button" class="warranty-success-close" onclick="closeWarrantySuccessOverlay()">OK</button>
    </div>
</div>

<script>
function toggleClaimPanel(id) {
    var panel = document.getElementById(id);
    if (!panel) return;
    panel.classList.toggle('open');
}

function openWarrantySuccessOverlay(claimNo) {
    var overlay = document.getElementById('warrantySuccessOverlay');
    var claimEl = document.getElementById('warrantySuccessClaimNo');
    if (claimEl) {
        claimEl.textContent = claimNo || '-';
    }
    if (overlay) {
        overlay.hidden = false;
        overlay.setAttribute('aria-hidden', 'false');
    }
}

function closeWarrantySuccessOverlay() {
    var overlay = document.getElementById('warrantySuccessOverlay');
    if (overlay) {
        overlay.hidden = true;
        overlay.setAttribute('aria-hidden', 'true');
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeWarrantySuccessOverlay();
    }
});

function markWarrantyClaimActive(form) {
    var card = form.closest('.warranty-card');
    if (!card) return;

    var btn = card.querySelector('.warranty-claim-btn');
    var panel = card.querySelector('.warranty-claim-panel');
    if (btn) btn.remove();
    if (panel) panel.remove();

    if (card.querySelector('.warranty-active-claim-note')) return;

    var note = document.createElement('div');
    note.className = 'warranty-active-claim-note';
    note.innerHTML = '<i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>'
        + 'A warranty claim is already active for this product. You can submit another claim only if the previous one is rejected.';
    card.appendChild(note);
}

document.querySelectorAll('.warranty-claim-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var msg = form.querySelector('.warranty-msg');
        var btn = form.querySelector('.warranty-submit-btn');
        var overlay = form.querySelector('.warranty-form-overlay');
        var fd = new FormData(form);
        fd.append('action', 'submit_warranty_claim');

        // Client-side validation: prevents showing "Submitting" UI when input is invalid.
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            msg.className = 'warranty-msg error';
            msg.textContent = 'Please fill in all required fields correctly.';
            if (typeof form.reportValidity === 'function') form.reportValidity();
            return;
        }
        var desc = form.querySelector('textarea[name="issue_description"]');
        if (desc) {
            var descLen = (desc.value || '').trim().length;
            if (descLen < 10) {
                msg.className = 'warranty-msg error';
                msg.textContent = 'Please provide a more detailed description (at least 10 characters).';
                desc.focus();
                return;
            }
        }

        if (overlay) {
            overlay.hidden = false;
            overlay.setAttribute('aria-hidden', 'false');
        }
        var originalBtnText = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        }
        msg.className = 'warranty-msg';
        msg.textContent = '';

        fetch('<?php echo BASE_URL; ?>controllers/WarrantyClaimController.php', {
            method: 'POST',
            body: fd
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (overlay) {
                overlay.hidden = true;
                overlay.setAttribute('aria-hidden', 'true');
            }
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
            }
            if (data.success) {
                msg.className = 'warranty-msg';
                msg.textContent = '';
                markWarrantyClaimActive(form);
                openWarrantySuccessOverlay(data.claim_number || '');
            } else {
                msg.className = 'warranty-msg error';
                msg.textContent = data.message || 'Could not submit claim.';
            }
        })
        .catch(function() {
            if (overlay) {
                overlay.hidden = true;
                overlay.setAttribute('aria-hidden', 'true');
            }
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
            }
            msg.className = 'warranty-msg error';
            msg.textContent = 'Network error. Please try again.';
        });
    });
});
</script>

<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
