<?php
$currentPage = 'sms-campaign';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/SmsLogDAL.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
$smsReady = ezymart_sms_is_configured();
$smsSettings = ezymart_sms_settings();
$smsGatewayLabel = ezymart_sms_provider_label($smsSettings['provider'] ?? 'notify_lk');
$orderPhoneCount = 0;
$userPhoneCount = 0;
$recentLogs = [];

try {
    $smsLog = new SmsLogDAL();
    $orderPhoneCount = count($smsLog->getDistinctOrderPhones());
    $userPhoneCount = count($smsLog->getDistinctUserPhones());
    $recentLogs = $smsLog->getAll();
} catch (Throwable $e) {}

$inputStyle = "width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>SMS Campaign - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <style>
        .dataTables_wrapper .dataTables_filter input {
            border: 1.5px solid #e4e8f0;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
            outline: none;
            font-family: 'Poppins', sans-serif;
        }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: #2563eb; }
        .dataTables_wrapper .dataTables_length select {
            border: 1.5px solid #e4e8f0;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
        }
        .dataTables_wrapper .dataTables_info {
            font-size: 12px;
            color: #6b7099;
            padding-top: 12px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            font-size: 12px;
            padding: 6px 12px !important;
            margin: 0 2px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #2563eb !important;
            color: #fff !important;
            border-color: #2563eb !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f0f2f8 !important;
            color: #0d1137 !important;
        }
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_length {
            margin-bottom: 16px;
            font-size: 13px;
        }
        .dataTables_wrapper { padding: 20px; }

        .sms-log-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 20px 0;
            border-bottom: 1px solid #f0f2f8;
        }
        .sms-log-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }
        .sms-log-filter {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #6b7099;
        }
        .sms-log-filter select {
            padding: 8px 12px;
            border: 1.5px solid #e4e8f0;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            color: #0d1137;
            background: #fff;
            cursor: pointer;
            outline: none;
        }
        .sms-log-filter select:focus { border-color: #2563eb; }

        .sms-status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .sms-status-badge.sent { background: #ecfdf5; color: #059669; }
        .sms-status-badge.failed { background: #fef2f2; color: #ef4444; }
    </style>
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
                        <h1 class="page-title">SMS Campaign</h1>
                        <p class="page-subtitle">Send bulk SMS via <?php echo htmlspecialchars($smsGatewayLabel); ?></p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>admin/site-settings.php" class="btn-primary" style="text-decoration:none;">
                        <i class="fas fa-cog"></i> SMS Settings
                    </a>
                </div>

                <?php if (!$smsReady): ?>
                <div class="admin-toast error" style="margin-bottom:20px;">
                    <i class="fas fa-exclamation-circle"></i>
                    SMS is not configured. Enable SMS and add credentials for your selected gateway (<?php echo htmlspecialchars($smsGatewayLabel); ?>) in <a href="<?php echo BASE_URL; ?>admin/site-settings.php" style="color:inherit; font-weight:700;">Site Settings</a>.
                </div>
                <?php endif; ?>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px;">
                    <div class="content-card" style="background:#fff; border:1px solid #eee; border-radius:14px; padding:24px;">
                        <p style="font-size:12px; color:#6b7099; margin:0 0 6px;">Order customers</p>
                        <h3 style="margin:0; font-size:28px; color:#0d1137;"><?php echo number_format($orderPhoneCount); ?></h3>
                    </div>
                    <div class="content-card" style="background:#fff; border:1px solid #eee; border-radius:14px; padding:24px;">
                        <p style="font-size:12px; color:#6b7099; margin:0 0 6px;">Registered users with phone</p>
                        <h3 style="margin:0; font-size:28px; color:#0d1137;"><?php echo number_format($userPhoneCount); ?></h3>
                    </div>
                </div>

                <div class="content-card" style="background:#fff; border:1px solid #eee; border-radius:14px; padding:28px; margin-bottom:24px;">
                    <h3 style="font-size:15px; font-weight:700; color:#0d1137; margin-bottom:18px; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-paper-plane" style="color:#003b94;"></i> Compose Campaign
                    </h3>
                    <div style="margin-bottom:14px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Campaign name (for logs)</label>
                        <input type="text" id="campaignName" placeholder="e.g. Weekend Sale" style="<?php echo $inputStyle; ?>">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Audience</label>
                        <select id="campaignAudience" style="<?php echo $inputStyle; ?>">
                            <option value="order_customers">All order customers (<?php echo $orderPhoneCount; ?>)</option>
                            <option value="registered_users">Registered users with phone (<?php echo $userPhoneCount; ?>)</option>
                            <option value="manual">Manual numbers</option>
                        </select>
                    </div>
                    <div id="manualNumbersWrap" style="display:none; margin-bottom:14px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Phone numbers</label>
                        <textarea id="manualNumbers" rows="3" placeholder="94771234567, 0701234567" style="<?php echo $inputStyle; ?> resize:vertical;"></textarea>
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Message</label>
                        <textarea id="campaignMessage" rows="5" maxlength="480" placeholder="Your promotional message..." style="<?php echo $inputStyle; ?> resize:vertical;"></textarea>
                        <p style="font-size:11px; color:#6b7099; margin-top:4px;"><span id="charCount">0</span>/480 characters. Store name is added automatically.</p>
                    </div>
                    <button type="button" id="sendCampaignBtn" <?php echo $smsReady ? '' : 'disabled'; ?> style="padding:12px 26px; background:#001f52; color:#ffffff; border:none; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
                        <i class="fas fa-paper-plane"></i> Send Campaign
                    </button>
                    <div id="campaignStatus" style="margin-top:14px; font-size:13px;"></div>
                </div>

                <div class="table-card">
                    <div class="sms-log-toolbar">
                        <h3 style="font-size:15px; font-weight:700; color:#0d1137; margin:0;">Recent SMS Log</h3>
                        <div class="sms-log-filters">
                            <div class="sms-log-filter">
                                <label for="smsStatusFilter"><i class="fas fa-filter"></i> Status</label>
                                <select id="smsStatusFilter" aria-label="Filter by status">
                                    <option value="all">All</option>
                                    <option value="sent">Sent</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                            <div class="sms-log-filter">
                                <label for="smsDateFilter"><i class="fas fa-calendar"></i> Date</label>
                                <select id="smsDateFilter" aria-label="Filter by date">
                                    <option value="all">All time</option>
                                    <option value="7">Last 7 days</option>
                                    <option value="30">Last 30 days</option>
                                    <option value="90">Last 90 days</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <table class="data-table" id="smsLogTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Recipient</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentLogs)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No SMS sent yet.</td>
                            </tr>
                            <?php else: foreach ($recentLogs as $log): ?>
                            <tr data-log-ts="<?php echo strtotime($log['created_at']); ?>" data-log-status="<?php echo htmlspecialchars($log['status']); ?>">
                                <td></td>
                                <td data-order="<?php echo strtotime($log['created_at']); ?>">
                                    <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($log['created_at']))); ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['campaign_name'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($log['recipient']); ?></td>
                                <td>
                                    <span class="sms-status-badge <?php echo $log['status'] === 'sent' ? 'sent' : 'failed'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($log['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="admin-footer" style="margin-top:40px;">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo ADMIN_BRAND; ?>. All rights reserved.</p>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.querySelector('.admin-layout').classList.toggle('sidebar-collapsed');
    });

    var smsLogTable = null;

    $(document).ready(function() {
        if (!document.getElementById('smsLogTable')) return;

        smsLogTable = $('#smsLogTable').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
            order: [[1, 'desc']],
            columnDefs: [
                { orderable: false, targets: [0] },
                { searchable: false, targets: [0] }
            ],
            language: {
                search: 'Search:',
                searchPlaceholder: 'Search recipient, type…',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ SMS logs',
                infoEmpty: 'No SMS logs found',
                infoFiltered: '(filtered from _MAX_ total)',
                zeroRecords: 'No matching SMS logs found',
                paginate: { previous: '‹', next: '›' }
            },
            drawCallback: function() {
                var api = this.api();
                var start = api.page.info().start;
                api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                    cell.textContent = start + i + 1;
                });
            }
        });

        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'smsLogTable') {
                return true;
            }

            var row = smsLogTable.row(dataIndex).node();
            var statusFilter = document.getElementById('smsStatusFilter').value;
            var dateFilter = document.getElementById('smsDateFilter').value;
            var rowStatus = row.getAttribute('data-log-status') || '';
            var ts = parseInt(row.getAttribute('data-log-ts') || '0', 10);

            if (statusFilter !== 'all' && rowStatus !== statusFilter) {
                return false;
            }

            if (dateFilter === 'all' || !ts) {
                return true;
            }

            var now = Math.floor(Date.now() / 1000);
            var days = parseInt(dateFilter, 10);
            if (!isNaN(days)) {
                return ts >= (now - (days * 86400));
            }

            return true;
        });

        document.getElementById('smsStatusFilter').addEventListener('change', function() {
            smsLogTable.draw();
        });
        document.getElementById('smsDateFilter').addEventListener('change', function() {
            smsLogTable.draw();
        });
    });

    var audience = document.getElementById('campaignAudience');
    var manualWrap = document.getElementById('manualNumbersWrap');
    audience.addEventListener('change', function() {
        manualWrap.style.display = this.value === 'manual' ? 'block' : 'none';
    });

    var msg = document.getElementById('campaignMessage');
    var charCount = document.getElementById('charCount');
    msg.addEventListener('input', function() { charCount.textContent = this.value.length; });

    document.getElementById('sendCampaignBtn').addEventListener('click', function() {
        var btn = this;
        var status = document.getElementById('campaignStatus');
        var message = msg.value.trim();
        if (!message) {
            status.innerHTML = '<span style="color:#ef4444;">Please enter a message.</span>';
            return;
        }
        if (!confirm('Send this SMS campaign now?')) return;

        btn.disabled = true;
        status.innerHTML = '<span style="color:#003b94;"><i class="fas fa-spinner fa-spin"></i> Sending...</span>';

        var form = new FormData();
        form.append('action', 'send');
        form.append('campaign_name', document.getElementById('campaignName').value.trim());
        form.append('audience', audience.value);
        form.append('manual_numbers', document.getElementById('manualNumbers').value.trim());
        form.append('message', message);

        fetch('<?php echo BASE_URL; ?>controllers/SmsCampaignController.php', {
            method: 'POST',
            body: form
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            if (data.success) {
                status.innerHTML = '<span style="color:#16a34a;"><i class="fas fa-check-circle"></i> ' + data.message + '</span>';
                setTimeout(function() { location.reload(); }, 2000);
            } else {
                status.innerHTML = '<span style="color:#ef4444;"><i class="fas fa-times-circle"></i> ' + (data.error || 'Failed') + '</span>';
            }
        })
        .catch(function() {
            btn.disabled = false;
            status.innerHTML = '<span style="color:#ef4444;">Network error. Please try again.</span>';
        });
    });
    </script>
</body>
</html>
