<?php
/**
 * Admin WhatsApp Orders Management
 */
$currentPage = 'whatsapp-orders';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/OrderDAL.php';
require_once __DIR__ . '/../helpers/order_notifications.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$orderDAL = new OrderDAL();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $newStatus = (string)($_POST['new_status'] ?? '');
        $result = lumak_apply_order_status_change(
            $orderDAL,
            $orderId,
            $newStatus,
            $_POST['tracking_id'] ?? null,
            $_POST['courier_company'] ?? null,
            true
        );

        if (lumak_admin_wants_json_response()) {
            lumak_admin_finish_json_request($result);
        }

        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'update_payment') {
        $orderId = (int)$_POST['order_id'];
        $newStatus = $_POST['payment_status'] ?? '';
        if ($orderId > 0 && in_array($newStatus, ['pending', 'paid', 'failed'])) {
            $order = $orderDAL->getById($orderId);
            $oldStatus = $order['payment_status'] ?? '';
            if ($order && $oldStatus !== $newStatus) {
                $orderDAL->updatePaymentStatus($orderId, $newStatus);
                lumak_send_payment_status_notification($orderId, $newStatus);
            }
            $message = 'Payment status updated!';
            $messageType = 'success';
        }

        if (lumak_admin_wants_json_response()) {
            lumak_admin_json_response([
                'success' => $messageType === 'success',
                'message' => $message ?: 'Payment status updated!',
            ]);
        }
    }
}

// Fetch WhatsApp orders only
$orders = $orderDAL->getByPaymentMethod('whatsapp');
$adminName = $_SESSION['user_name'] ?? 'Admin';

// Stats
$totalOrders = count($orders);
$pendingOrders = count(array_filter($orders, fn($o) => $o['order_status'] === 'pending'));
$processingOrders = count(array_filter($orders, fn($o) => $o['order_status'] === 'processing'));
$deliveredOrders = count(array_filter($orders, fn($o) => $o['order_status'] === 'delivered'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>WhatsApp Orders - Admin | <?php echo ADMIN_BRAND; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .dataTables_wrapper .dataTables_filter input { border: 1.5px solid #e4e8f0; border-radius: 8px; padding: 8px 14px; font-size: 13px; outline: none; font-family: 'Poppins', sans-serif; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: #25d366; }
        .dataTables_wrapper .dataTables_length select { border: 1.5px solid #e4e8f0; border-radius: 8px; padding: 6px 10px; font-size: 13px; font-family: 'Poppins', sans-serif; }
        .dataTables_wrapper .dataTables_info { font-size: 12px; color: #6b7099; padding-top: 12px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 8px !important; font-size: 12px; padding: 6px 12px !important; margin: 0 2px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #25d366 !important; color: #fff !important; border-color: #25d366 !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #f0f2f8 !important; color: #0d1137 !important; }
        .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length { margin-bottom: 16px; font-size: 13px; }
        .dataTables_wrapper { padding: 20px; }
        
        .order-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .order-stat-card { background: #fff; border-radius: 12px; padding: 18px; border: 1px solid #eee; text-align: center; }
        .order-stat-card h4 { font-size: 24px; font-weight: 700; color: #0d1137; }
        .order-stat-card p { font-size: 11px; color: #6b7099; margin-top: 4px; }
        .order-stat-card.total h4 { color: #25d366; }
        .order-stat-card.pending h4 { color: #ea580c; }
        .order-stat-card.processing h4 { color: #2563eb; }
        .order-stat-card.delivered h4 { color: #16a34a; }

        .order-number { font-weight: 700; color: #25d366; font-size: 12px; }
        .order-status-select, .payment-status-select { padding: 5px 10px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 11px; font-family: 'Poppins', sans-serif; cursor: pointer; }
        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; display: inline-block; }
        .status-badge.pending { background: #fff7ed; color: #ea580c; }
        .status-badge.processing { background: #eff6ff; color: #2563eb; }
        .status-badge.shipped { background: #f0fdf4; color: #16a34a; }
        .status-badge.delivered { background: #ecfdf5; color: #059669; }
        .status-badge.cancelled { background: #fef2f2; color: #ef4444; }
        .status-badge.paid { background: #ecfdf5; color: #16a34a; }
        .status-badge.failed { background: #fef2f2; color: #ef4444; }

        .btn-view-order { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; background: #eff6ff; color: #2563eb; border: none; border-radius: 6px; font-size: 11px; font-weight: 500; cursor: pointer; font-family: 'Poppins', sans-serif; text-decoration: none; transition: all 0.2s; }
        .btn-view-order:hover { background: #2563eb; color: #fff; }
        .btn-print-label { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; background: #f0f2f8; color: #0d1137; border: none; border-radius: 6px; font-size: 11px; font-weight: 500; cursor: pointer; font-family: 'Poppins', sans-serif; transition: all 0.2s; }
        .btn-print-label:hover { background: #0d1137; color: #fff; }

        .wa-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; background: #ecfdf5; color: #25d366; border-radius: 12px; font-size: 10px; font-weight: 600; }

        @media (max-width: 768px) { .order-stats { grid-template-columns: repeat(2, 1fr); } }
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
                        <h1 class="page-title"><i class="fab fa-whatsapp" style="color:#25d366;"></i> WhatsApp Orders</h1>
                        <p class="page-subtitle">Manage orders placed via WhatsApp</p>
                    </div>
                </div>

                <?php if ($message): ?>
                <script>
                (function() {
                    var toast = document.createElement('div');
                    toast.style.cssText = 'position:fixed; bottom:30px; right:30px; z-index:9999; background:#fff; color:<?php echo $messageType === "success" ? "#16a34a" : "#dc2626"; ?>; padding:14px 20px; border-radius:12px; font-size:13px; display:flex; align-items:center; gap:10px; box-shadow:0 8px 30px rgba(0,0,0,0.12); border-left:4px solid <?php echo $messageType === "success" ? "#16a34a" : "#dc2626"; ?>; animation:slideInRight 0.4s ease, fadeOutRight 0.5s ease 4s forwards; font-family:Poppins,sans-serif;';
                    toast.innerHTML = '<i class="fas fa-<?php echo $messageType === "success" ? "check-circle" : "exclamation-circle"; ?>"></i> <?php echo htmlspecialchars($message); ?>';
                    document.body.appendChild(toast);
                })();
                </script>
                <style>
                @keyframes slideInRight { from { opacity:0; transform:translateX(100%); } to { opacity:1; transform:translateX(0); } }
                @keyframes fadeOutRight { from { opacity:1; transform:translateX(0); } to { opacity:0; transform:translateX(100%); } }
                </style>
                <?php endif; ?>

                <!-- Order Stats -->
                <div class="order-stats">
                    <div class="order-stat-card total">
                        <h4><?php echo $totalOrders; ?></h4>
                        <p>WhatsApp Orders</p>
                    </div>
                    <div class="order-stat-card pending">
                        <h4><?php echo $pendingOrders; ?></h4>
                        <p>Pending</p>
                    </div>
                    <div class="order-stat-card processing">
                        <h4><?php echo $processingOrders; ?></h4>
                        <p>Processing</p>
                    </div>
                    <div class="order-stat-card delivered">
                        <h4><?php echo $deliveredOrders; ?></h4>
                        <p>Delivered</p>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="table-card">
                    <table class="data-table" id="waOrdersTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr><td colspan="8" style="text-align:center; padding:40px; color:#888;">
                                <i class="fab fa-whatsapp" style="font-size:2rem; color:#ddd; display:block; margin-bottom:10px;"></i>
                                No WhatsApp orders yet.
                            </td></tr>
                            <?php else: ?>
                            <?php foreach ($orders as $order): 
                                $items = $orderDAL->getOrderItems($order['id']);
                                $itemCount = array_sum(array_column($items, 'quantity'));
                            ?>
                            <tr>
                                <td>
                                    <span class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                                    <br><span class="wa-badge"><i class="fab fa-whatsapp"></i> WhatsApp</span>
                                </td>
                                <td>
                                    <strong style="font-size:12px; display:block;"><?php echo htmlspecialchars($order['full_name']); ?></strong>
                                    <small style="color:#888;"><?php echo htmlspecialchars($order['phone']); ?></small>
                                </td>
                                <td><span class="badge-count"><?php echo $itemCount; ?></span></td>
                                <td><strong style="font-size:13px;">LKR <?php echo number_format($order['total'], 0); ?></strong></td>
                                <td>
                                    <select class="payment-status-select" onchange="updateStatus('payment', <?php echo $order['id']; ?>, this.value)">
                                        <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="order-status-select" data-prev-status="<?php echo htmlspecialchars($order['order_status']); ?>" onchange="handleAdminOrderStatusChange(this, <?php echo (int)$order['id']; ?>)">
                                        <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </td>
                                <td><small style="color:#888;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></small></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>admin/order-detail.php?id=<?php echo $order['id']; ?>&from=whatsapp" class="btn-view-order"><i class="fas fa-eye"></i> View</a>
                                    <button class="btn-print-label" onclick="printLabel(<?php echo $order['id']; ?>)"><i class="fas fa-print"></i> Label</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="admin-footer">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo ADMIN_BRAND; ?>. All rights reserved. Developed by Chandila.</p>
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

    $(document).ready(function() {
        $('#waOrdersTable').DataTable({
            pageLength: 10,
            order: [[6, 'desc']],
            columnDefs: [{ orderable: false, targets: [7] }],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ orders",
                paginate: { previous: "‹", next: "›" }
            }
        });
    });

    function printLabel(orderId) {
        window.open('<?php echo BASE_URL; ?>admin/print-label.php?id=' + orderId, '_blank', 'width=520,height=760');
    }

    function updatePaymentStatus(orderId, value) {
        var formData = new FormData();
        formData.append('action', 'update_payment');
        formData.append('order_id', orderId);
        formData.append('payment_status', value);

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(r) { return r.json().catch(function() { return { success: true }; }); })
        .then(function(result) {
            var toast = document.createElement('div');
            toast.style.cssText = 'position:fixed; bottom:30px; right:30px; z-index:9999; background:#fff; color:#16a34a; padding:14px 20px; border-radius:12px; font-size:13px; display:flex; align-items:center; gap:10px; box-shadow:0 8px 30px rgba(0,0,0,0.12); border-left:4px solid #16a34a; font-family:Poppins,sans-serif;';
            toast.innerHTML = '<i class="fas fa-check-circle"></i> ' + ((result && result.message) || 'Payment status updated!');
            document.body.appendChild(toast);
            setTimeout(function() { toast.remove(); }, 3000);
        });
    }

    window.submitAdminOrderStatus = function(orderId, value, shipping) {
        var formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('order_id', orderId);
        formData.append('new_status', value);
        if (shipping) {
            formData.append('tracking_id', shipping.tracking_id);
            formData.append('courier_company', shipping.courier_company);
        }

        return fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(r) { return r.json(); });
    };

    function updateStatus(type, orderId, value) {
        if (type === 'payment') {
            updatePaymentStatus(orderId, value);
        }
    }
    </script>
    <?php include __DIR__ . '/partials/shipping-overlay.php'; ?>
</body>
</html>
