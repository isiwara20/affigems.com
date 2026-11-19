<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/WarrantyClaimDAL.php';
require_once __DIR__ . '/../helpers/warranty_notifications.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$claimDAL = new WarrantyClaimDAL();
$message = '';
$messageType = '';
$statusFilter = $_GET['status'] ?? 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $notes = trim($_POST['admin_notes'] ?? '');

    if ($action === 'update_status' && $id > 0) {
        $newStatus = $_POST['status'] ?? 'pending';
        if ($claimDAL->updateStatus($id, $newStatus, $notes !== '' ? $notes : null)) {
            $message = 'Claim status updated.';
            $messageType = 'success';

            $updatedClaim = $claimDAL->getById($id);
            if ($updatedClaim) {
                $updatedClaim['admin_notes'] = $notes !== '' ? $notes : ($updatedClaim['admin_notes'] ?? null);
                if (!empty($updatedClaim['attachment'])) {
                    $updatedClaim['attachment_url'] = function_exists('absolute_asset_image_url')
                        ? absolute_asset_image_url($updatedClaim['attachment'])
                        : BASE_URL . ltrim($updatedClaim['attachment'], '/');
                }
                ezymart_warranty_notify_admin($updatedClaim);
                ezymart_warranty_notify_customer($updatedClaim);
            }
        } else {
            $message = 'Could not update claim status.';
            $messageType = 'error';
        }
    }
}

$claims = $claimDAL->getAll($statusFilter);
$pendingCount = $claimDAL->countPending();
$adminName = $_SESSION['user_name'] ?? 'Admin';
$currentPage = 'warranty-claims';
$claimsJsMap = [];

function warranty_claim_attachment_url(?string $path): string
{
    if (!$path) {
        return '';
    }
    return function_exists('media_image_url') ? media_image_url($path) : BASE_URL . ltrim($path, '/');
}

function warranty_claim_is_video(?string $path): bool
{
    if (!$path) {
        return false;
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'mov', 'webm'], true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Warranty Claims - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .filter-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
        .filter-tabs a { padding:8px 14px; border-radius:8px; font-size:12px; font-weight:600; text-decoration:none; background:#f0f2f8; color:#333; }
        .filter-tabs a.active { background:#002868; color:#fff; }
        .warranty-table-card {
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }
        .warranty-table-card .dataTables_wrapper {
            padding: 16px;
            width: 100% !important;
            max-width: 100%;
            overflow-x: auto;
        }
        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            border: 1.5px solid #e4e8f0; border-radius: 8px; padding: 8px 12px;
            font-family: 'Poppins', sans-serif; font-size: 12px;
        }
        .claim-status-badge {
            display: inline-block; padding: 4px 10px; border-radius: 20px;
            font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;
            white-space: nowrap;
        }
        .claim-status-badge.pending { background:#fff7ed; color:#ea580c; }
        .claim-status-badge.approved { background:#eff6ff; color:#003b94; }
        .claim-status-badge.replaced { background:#ecfdf5; color:#16a34a; }
        .claim-status-badge.rejected { background:#fef2f2; color:#ef4444; }
        .claim-cell-main,
        .claim-cell-sub {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 100%;
        }
        .claim-cell-main { font-weight: 600; color: #0d1137; font-size: 12px; }
        .claim-cell-sub { font-size: 11px; color: #6b7099; margin-top: 2px; }
        .claim-issue-preview {
            font-size: 11px; color: #6b7099; margin-top: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 100%;
        }
        #warrantyClaimsTable {
            table-layout: fixed !important;
            width: 100% !important;
            max-width: 100%;
        }
        #warrantyClaimsTable th,
        #warrantyClaimsTable td {
            vertical-align: middle;
            overflow: hidden;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        #warrantyClaimsTable th:nth-child(1),
        #warrantyClaimsTable td:nth-child(1) { width: 36px; }
        #warrantyClaimsTable th:nth-child(2),
        #warrantyClaimsTable td:nth-child(2) { width: 12%; }
        #warrantyClaimsTable th:nth-child(3),
        #warrantyClaimsTable td:nth-child(3) { width: 10%; }
        #warrantyClaimsTable th:nth-child(4),
        #warrantyClaimsTable td:nth-child(4) { width: 16%; }
        #warrantyClaimsTable th:nth-child(5),
        #warrantyClaimsTable td:nth-child(5) { width: 16%; }
        #warrantyClaimsTable th:nth-child(6),
        #warrantyClaimsTable td:nth-child(6) { width: 14%; }
        #warrantyClaimsTable th:nth-child(7),
        #warrantyClaimsTable td:nth-child(7) { width: 90px; }
        #warrantyClaimsTable th:nth-child(8),
        #warrantyClaimsTable td:nth-child(8) { width: 90px; }
        #warrantyClaimsTable th:nth-child(9),
        #warrantyClaimsTable td:nth-child(9) { width: 130px; white-space: nowrap; overflow: visible; }
        .claim-action-btn {
            border: none; background: #f0f5fc; color: #002868; border-radius: 8px;
            padding: 7px 10px; font-size: 11px; font-weight: 600; cursor: pointer;
            font-family: 'Poppins', sans-serif; margin: 2px 2px 2px 0;
            display: inline-block;
        }
        .claim-action-btn:hover { background: #002868; color: #fff; }
        .claim-action-btn.attach { background: #ecfdf5; color: #16a34a; }
        .claim-action-btn.attach:hover { background: #16a34a; color: #fff; }
        .admin-modal-overlay {
            position: fixed; inset: 0; background: rgba(13,17,55,0.55);
            display: flex; align-items: center; justify-content: center;
            z-index: 5000; padding: 20px;
        }
        .admin-modal-overlay[hidden] { display: none !important; }
        .admin-modal {
            width: min(720px, 100%); max-height: 90vh; overflow: auto;
            background: #fff; border-radius: 16px; border: 1px solid #e4e8f0;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2); padding: 24px;
        }
        .admin-modal h3 { margin: 0 0 6px; font-size: 18px; color: #0d1137; word-break: break-word; }
        .admin-modal-meta { font-size: 12px; color: #6b7099; margin-bottom: 16px; word-break: break-word; }
        .admin-modal-body {
            background: #f8f9fc; border-radius: 10px; padding: 14px;
            font-size: 13px; color: #444; line-height: 1.7; margin-bottom: 16px;
            white-space: pre-wrap; word-break: break-word; overflow-wrap: anywhere;
            max-height: 220px; overflow-y: auto;
        }
        .admin-modal-body-label {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.04em; color: #6b7099; margin-bottom: 8px;
        }
        .admin-modal-form label { display: block; font-size: 12px; font-weight: 600; color: #333; margin-bottom: 6px; }
        .admin-modal-form select,
        .admin-modal-form textarea {
            width: 100%; padding: 10px 12px; border: 1.5px solid #e4e8f0;
            border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 13px; margin-bottom: 12px;
            box-sizing: border-box;
        }
        .admin-modal-form textarea { min-height: 80px; resize: vertical; }
        .admin-modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-modal-primary {
            padding: 10px 18px; background: #002868; color: #fff; border: none;
            border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }
        .btn-modal-secondary {
            padding: 10px 18px; background: #f0f2f8; color: #555; border: none;
            border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }
        .attachment-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.85);
            display: flex; align-items: center; justify-content: center;
            z-index: 6000; padding: 24px;
        }
        .attachment-overlay[hidden] { display: none !important; }
        .attachment-overlay-inner {
            position: relative; max-width: min(920px, 100%); max-height: 90vh;
            background: #111; border-radius: 12px; overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }
        .attachment-overlay-inner img,
        .attachment-overlay-inner video {
            display: block; max-width: 100%; max-height: 85vh; margin: 0 auto;
        }
        .attachment-close {
            position: absolute; top: 12px; right: 12px; width: 36px; height: 36px;
            border: none; border-radius: 50%; background: rgba(255,255,255,0.15);
            color: #fff; font-size: 18px; cursor: pointer; z-index: 2;
        }
        .attachment-close:hover { background: rgba(255,255,255,0.3); }
        .attachment-toolbar {
            padding: 10px 14px; background: #1a1a2e; color: #fff;
            font-size: 12px; display: flex; justify-content: space-between; align-items: center;
        }
        .attachment-toolbar a { color: #93c5fd; text-decoration: none; font-weight: 600; }
        @media (max-width: 992px) {
            .warranty-table-card .dataTables_wrapper { overflow-x: auto; }
            #warrantyClaimsTable { min-width: 900px; }
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
                        <h1 class="page-title">
                            Warranty Claims
                            <?php if ($pendingCount > 0): ?>
                            <span style="background:#ea580c;color:#fff;font-size:12px;padding:4px 10px;border-radius:12px;margin-left:8px;"><?php echo $pendingCount; ?> pending</span>
                            <?php endif; ?>
                        </h1>
                        <p class="page-subtitle">Review and update customer warranty requests</p>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="admin-toast <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <div class="filter-tabs">
                    <?php foreach (['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'replaced' => 'Replaced', 'rejected' => 'Rejected'] as $key => $label): ?>
                    <a href="?status=<?php echo $key; ?>" class="<?php echo $statusFilter === $key ? 'active' : ''; ?>"><?php echo $label; ?></a>
                    <?php endforeach; ?>
                </div>

                <div class="table-card warranty-table-card">
                    <table class="data-table" id="warrantyClaimsTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Claim</th>
                                <th>Product</th>
                                <th>Customer</th>
                                <th>Invoice / Serial</th>
                                <th>Issue</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($claims)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No warranty claims found.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($claims as $i => $claim):
                                $status = strtolower((string)($claim['status'] ?? 'pending'));
                                $attachmentUrl = warranty_claim_attachment_url($claim['attachment'] ?? '');
                                $isVideo = warranty_claim_is_video($claim['attachment'] ?? '');
                                $issueDesc = (string)($claim['issue_description'] ?? '');
                                $issuePreview = function_exists('mb_strimwidth')
                                    ? mb_strimwidth($issueDesc, 0, 36, '…')
                                    : (strlen($issueDesc) > 36 ? substr($issueDesc, 0, 33) . '…' : $issueDesc);
                                $claimsJsMap[(int)$claim['id']] = [
                                    'id' => (int)$claim['id'],
                                    'claim_number' => $claim['claim_number'],
                                    'product_name' => $claim['product_name'],
                                    'customer_name' => $claim['customer_name'],
                                    'phone' => $claim['phone'],
                                    'email' => $claim['email'] ?? '',
                                    'invoice_number' => $claim['invoice_number'],
                                    'serial_number' => $claim['serial_number'],
                                    'issue_type' => $claim['issue_type'],
                                    'issue_description' => $issueDesc,
                                    'status' => $status,
                                    'admin_notes' => $claim['admin_notes'] ?? '',
                                    'created_at' => date('d M Y, h:i A', strtotime($claim['created_at'])),
                                ];
                            ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td>
                                    <div class="claim-cell-main"><?php echo htmlspecialchars($claim['claim_number']); ?></div>
                                </td>
                                <td>
                                    <div class="claim-cell-main"><?php echo htmlspecialchars($claim['product_name']); ?></div>
                                </td>
                                <td>
                                    <div class="claim-cell-main" title="<?php echo htmlspecialchars($claim['customer_name']); ?>"><?php echo htmlspecialchars($claim['customer_name']); ?></div>
                                    <div class="claim-cell-sub" title="<?php echo htmlspecialchars($claim['phone']); ?>"><?php echo htmlspecialchars($claim['phone']); ?></div>
                                </td>
                                <td>
                                    <div class="claim-cell-main" title="<?php echo htmlspecialchars($claim['invoice_number']); ?>"><?php echo htmlspecialchars($claim['invoice_number']); ?></div>
                                    <div class="claim-cell-sub" title="<?php echo htmlspecialchars($claim['serial_number']); ?>"><?php echo htmlspecialchars($claim['serial_number']); ?></div>
                                </td>
                                <td>
                                    <div class="claim-cell-main" title="<?php echo htmlspecialchars($claim['issue_type']); ?>"><?php echo htmlspecialchars($claim['issue_type']); ?></div>
                                    <div class="claim-issue-preview" title="<?php echo htmlspecialchars($issueDesc); ?>">
                                        <?php echo htmlspecialchars($issuePreview); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="claim-status-badge <?php echo htmlspecialchars($status); ?>">
                                        <?php echo htmlspecialchars(ezymart_warranty_claim_status_label($status)); ?>
                                    </span>
                                </td>
                                <td data-order="<?php echo strtotime($claim['created_at']); ?>">
                                    <?php echo date('d M Y', strtotime($claim['created_at'])); ?><br>
                                    <span class="claim-cell-sub"><?php echo date('h:i A', strtotime($claim['created_at'])); ?></span>
                                </td>
                                <td>
                                    <button type="button" class="claim-action-btn" onclick="openClaimModal(<?php echo (int)$claim['id']; ?>)">
                                        <i class="fas fa-edit"></i> Manage
                                    </button>
                                    <?php if ($attachmentUrl): ?>
                                    <button type="button" class="claim-action-btn attach" onclick="openAttachmentOverlay('<?php echo htmlspecialchars($attachmentUrl, ENT_QUOTES); ?>', <?php echo $isVideo ? 'true' : 'false'; ?>, '<?php echo htmlspecialchars($claim['claim_number'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-paperclip"></i> View
                                    </button>
                                    <?php else: ?>
                                    <span class="claim-cell-sub">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="admin-footer">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo ADMIN_BRAND; ?>. All rights reserved.</p>
                </div>
            </div>
        </main>
    </div>

    <?php if (!empty($claimsJsMap)): ?>
    <script>
    window.warrantyClaimsData = <?php echo json_encode($claimsJsMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
    </script>
    <?php else: ?>
    <script>window.warrantyClaimsData = {};</script>
    <?php endif; ?>

    <!-- Manage claim modal -->
    <div id="claimModal" class="admin-modal-overlay" hidden aria-hidden="true">
        <div class="admin-modal" role="dialog" aria-modal="true">
            <h3 id="modalClaimTitle">Manage Claim</h3>
            <div class="admin-modal-meta" id="modalClaimMeta"></div>
            <div class="admin-modal-body-label">Issue description</div>
            <div class="admin-modal-body" id="modalClaimDescription"></div>
            <form method="POST" class="admin-modal-form" id="claimUpdateForm">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="modalClaimId" value="">
                <label for="modalClaimStatus">Status</label>
                <select name="status" id="modalClaimStatus">
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="replaced">Replaced</option>
                    <option value="rejected">Rejected</option>
                </select>
                <label for="modalClaimNotes">Admin notes (optional)</label>
                <textarea name="admin_notes" id="modalClaimNotes" placeholder="Internal notes or message for customer email..."></textarea>
                <div class="admin-modal-actions">
                    <button type="button" class="btn-modal-secondary" onclick="closeClaimModal()">Cancel</button>
                    <button type="submit" class="btn-modal-primary"><i class="fas fa-save"></i> Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Attachment overlay -->
    <div id="attachmentOverlay" class="attachment-overlay" hidden aria-hidden="true" onclick="if(event.target===this) closeAttachmentOverlay()">
        <div class="attachment-overlay-inner">
            <button type="button" class="attachment-close" onclick="closeAttachmentOverlay()" aria-label="Close">&times;</button>
            <div class="attachment-toolbar">
                <span id="attachmentLabel">Attachment</span>
                <a id="attachmentDownload" href="#" target="_blank" rel="noopener">Open in new tab</a>
            </div>
            <div id="attachmentContent"></div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.querySelector('.admin-layout').classList.toggle('sidebar-collapsed');
    });

    $(document).ready(function() {
        if (document.getElementById('warrantyClaimsTable') && $('#warrantyClaimsTable tbody tr').length > 0 && !$('#warrantyClaimsTable tbody tr td[colspan]').length) {
            $('#warrantyClaimsTable').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
                order: [[7, 'desc']],
                autoWidth: false,
                scrollX: false,
                columnDefs: [
                    { orderable: false, targets: [0, 8] },
                    { searchable: false, targets: [0, 8] },
                    { width: '36px', targets: 0 },
                    { width: '130px', targets: 8 }
                ],
                language: {
                    search: 'Search:',
                    searchPlaceholder: 'Claim, product, customer…',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ claims',
                    zeroRecords: 'No matching claims found'
                }
            });
        }
    });

    function openClaimModal(claimId) {
        var claim = (window.warrantyClaimsData || {})[claimId];
        if (!claim) {
            alert('Could not load claim details.');
            return;
        }
        document.getElementById('modalClaimId').value = claim.id;
        document.getElementById('modalClaimTitle').textContent = claim.claim_number + ' — ' + claim.product_name;
        document.getElementById('modalClaimMeta').innerHTML =
            '<strong>' + escapeHtml(claim.customer_name) + '</strong> · ' + escapeHtml(claim.phone) +
            (claim.email ? ' · ' + escapeHtml(claim.email) : '') +
            '<br>Invoice: ' + escapeHtml(claim.invoice_number) + ' · Serial: ' + escapeHtml(claim.serial_number) +
            '<br>Issue type: ' + escapeHtml(claim.issue_type) + ' · ' + escapeHtml(claim.created_at);
        document.getElementById('modalClaimDescription').textContent = claim.issue_description || 'No description provided.';
        document.getElementById('modalClaimStatus').value = claim.status;
        document.getElementById('modalClaimNotes').value = claim.admin_notes || '';
        var modal = document.getElementById('claimModal');
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeClaimModal() {
        var modal = document.getElementById('claimModal');
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
    }

    function openAttachmentOverlay(url, isVideo, claimNo) {
        var overlay = document.getElementById('attachmentOverlay');
        var content = document.getElementById('attachmentContent');
        var label = document.getElementById('attachmentLabel');
        var download = document.getElementById('attachmentDownload');
        label.textContent = 'Attachment — ' + claimNo;
        download.href = url;
        content.innerHTML = isVideo
            ? '<video src="' + url + '" controls autoplay style="width:100%;"></video>'
            : '<img src="' + url + '" alt="Claim attachment">';
        overlay.hidden = false;
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeAttachmentOverlay() {
        var overlay = document.getElementById('attachmentOverlay');
        var content = document.getElementById('attachmentContent');
        overlay.hidden = true;
        overlay.setAttribute('aria-hidden', 'true');
        content.innerHTML = '';
        document.body.style.overflow = '';
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeClaimModal();
            closeAttachmentOverlay();
        }
    });
    </script>
</body>
</html>
