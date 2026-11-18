<?php
$currentPage = 'subscribers';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/SubscriberDAL.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$subscriberDAL = new SubscriberDAL();
$adminName = $_SESSION['user_name'] ?? 'Admin';
$subscribers = $subscriberDAL->getActive();
$totalSubs = count($subscribers);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $subscriberDAL->delete((int)$_POST['id']);
    header('Location: ' . BASE_URL . 'admin/subscribers.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Subscribers - Admin | ARIGATO.LK</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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

        .subscriber-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 20px 0;
            border-bottom: 1px solid #f0f2f8;
        }
        .subscriber-filter {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #6b7099;
        }
        .subscriber-filter select {
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
        .subscriber-filter select:focus { border-color: #2563eb; }

        .subscriber-email {
            font-size: 13px;
            font-weight: 500;
            color: #0d1137;
            word-break: break-all;
        }
        .subscriber-date {
            font-size: 12px;
            color: #6b7099;
            white-space: nowrap;
        }
        .btn-subscriber-delete {
            background: #fef2f2;
            color: #ef4444;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: all 0.2s;
        }
        .btn-subscriber-delete:hover {
            background: #ef4444;
            color: #fff;
        }

        #newsletterForm {
            display: none;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 14px;
            padding: 28px;
            margin-bottom: 24px;
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
                        <h1 class="page-title">Newsletter Subscribers</h1>
                        <p class="page-subtitle"><?php echo $totalSubs; ?> active subscriber<?php echo $totalSubs !== 1 ? 's' : ''; ?></p>
                    </div>
                    <button type="button" class="btn-primary" onclick="document.getElementById('newsletterForm').style.display='block'">
                        <i class="fas fa-paper-plane"></i> Send Newsletter
                    </button>
                </div>

                <div id="newsletterForm">
                    <h3 style="font-size:15px; font-weight:700; color:#0d1137; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-envelope" style="color:#e8600a;"></i> Compose Newsletter
                    </h3>
                    <div style="margin-bottom:14px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Subject</label>
                        <input type="text" id="nlSubject" placeholder="Newsletter subject..." style="width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none;">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px;">Message</label>
                        <textarea id="nlBody" rows="8" placeholder="Write your newsletter message. Plain text or HTML is supported (e.g. &lt;p&gt;, &lt;strong&gt;, &lt;a href=&quot;...&quot;&gt;, &lt;img&gt;)." style="width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none; resize:vertical;"></textarea>
                        <p style="margin:6px 0 0; font-size:11px; color:#888; line-height:1.5;">HTML tags in this field are sent as formatted email content. Plain text is also supported.</p>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button type="button" onclick="sendNewsletter()" style="padding:11px 24px; background:#8A6508; color:#fff; border:none; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-paper-plane"></i> Send to All (<?php echo $totalSubs; ?>)
                        </button>
                        <button type="button" onclick="document.getElementById('newsletterForm').style.display='none'" style="padding:11px 18px; background:#f0f2f8; color:#555; border:none; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; font-weight:600; cursor:pointer;">
                            Cancel
                        </button>
                    </div>
                    <div id="nlStatus" style="margin-top:12px; font-size:13px;"></div>
                </div>

                <div class="table-card">
                    <div class="subscriber-toolbar">
                        <div class="subscriber-filter">
                            <label for="subscriberDateFilter"><i class="fas fa-filter"></i> Filter by date</label>
                            <select id="subscriberDateFilter" aria-label="Filter subscribers by date">
                                <option value="all">All subscribers</option>
                                <option value="7">Last 7 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 90 days</option>
                                <option value="year">This year</option>
                            </select>
                        </div>
                    </div>

                    <table class="data-table" id="subscribersTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Email</th>
                                <th>Subscribed</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($subscribers)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No subscribers yet.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($subscribers as $sub): ?>
                            <tr data-subscribed-ts="<?php echo strtotime($sub['created_at']); ?>">
                                <td></td>
                                <td><span class="subscriber-email"><?php echo htmlspecialchars($sub['email']); ?></span></td>
                                <td data-order="<?php echo strtotime($sub['created_at']); ?>">
                                    <span class="subscriber-date"><?php echo date('M j, Y', strtotime($sub['created_at'])); ?></span>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this subscriber?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$sub['id']; ?>">
                                        <button type="submit" class="btn-subscriber-delete" title="Remove subscriber">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="admin-footer">
                    <p>&copy; 2026 ARIGATO.LK. All rights reserved.</p>
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

    var subscribersTable = null;

    $(document).ready(function() {
        if (!document.getElementById('subscribersTable')) return;

        subscribersTable = $('#subscribersTable').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
            order: [[2, 'desc']],
            columnDefs: [
                { orderable: false, targets: [0, 3] },
                { searchable: false, targets: [0, 3] }
            ],
            language: {
                search: 'Search:',
                searchPlaceholder: 'Search email…',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ subscribers',
                infoEmpty: 'No subscribers found',
                infoFiltered: '(filtered from _MAX_ total)',
                zeroRecords: 'No matching subscribers found',
                paginate: { previous: '‹', next: '›' }
            },
            drawCallback: function(settings) {
                var api = this.api();
                var start = api.page.info().start;
                api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                    cell.textContent = start + i + 1;
                });
            }
        });

        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'subscribersTable') {
                return true;
            }

            var filter = document.getElementById('subscriberDateFilter').value;
            if (filter === 'all') {
                return true;
            }

            var row = subscribersTable.row(dataIndex).node();
            var ts = parseInt(row.getAttribute('data-subscribed-ts') || '0', 10);
            if (!ts) {
                return true;
            }

            var now = Math.floor(Date.now() / 1000);
            var days = parseInt(filter, 10);

            if (!isNaN(days)) {
                return ts >= (now - (days * 86400));
            }

            if (filter === 'year') {
                var rowDate = new Date(ts * 1000);
                return rowDate.getFullYear() === new Date().getFullYear();
            }

            return true;
        });

        document.getElementById('subscriberDateFilter').addEventListener('change', function() {
            subscribersTable.draw();
        });
    });

    function sendNewsletter() {
        var subject = document.getElementById('nlSubject').value.trim();
        var body = document.getElementById('nlBody').value.trim();
        var status = document.getElementById('nlStatus');

        if (!subject || !body) {
            status.innerHTML = '<span style="color:#ef4444;">Please fill in subject and message.</span>';
            return;
        }

        status.innerHTML = '<span style="color:#1a1f4e;"><i class="fas fa-spinner fa-spin"></i> Sending to all subscribers...</span>';

        fetch('<?php echo BASE_URL; ?>controllers/SubscribeController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=send_newsletter&subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                status.innerHTML = '<span style="color:#16a34a;"><i class="fas fa-check-circle"></i> ' + data.message + '</span>';
                document.getElementById('nlSubject').value = '';
                document.getElementById('nlBody').value = '';
            } else {
                status.innerHTML = '<span style="color:#ef4444;"><i class="fas fa-exclamation-circle"></i> ' + data.error + '</span>';
            }
        });
    }
    </script>
</body>
</html>
