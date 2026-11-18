<?php
/**
 * Admin Order Detail
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/OrderDAL.php';
require_once __DIR__ . '/../dal/OrderItemSerialDAL.php';
require_once __DIR__ . '/../helpers/order_notifications.php';
require_once __DIR__ . '/../helpers/warranty.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$orderDAL = new OrderDAL();
$serialDAL = new OrderItemSerialDAL();
$orderId = (int)($_GET['id'] ?? 0);
$from = $_GET['from'] ?? '';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_serials'])) {
    $fromParam = $_POST['from'] ?? '';
    $postedSerials = $_POST['serials'] ?? [];
    if (!is_array($postedSerials)) {
        $postedSerials = [];
    }

    $result = ezymart_warranty_save_order_serials($orderId, $postedSerials);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';

    header('Location: ' . BASE_URL . 'admin/order-detail.php?id=' . $orderId
        . ($fromParam ? '&from=' . urlencode($fromParam) : '')
        . '&msg=' . urlencode($message)
        . '&msgtype=' . ($result['success'] ? 'success' : 'error'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newOrderStatus = (string)($_POST['order_status'] ?? '');
    $newPaymentStatus = $_POST['payment_status'] ?? '';
    $fromParam = $_POST['from'] ?? '';

    if (!empty($newOrderStatus)) {
        $result = lumak_apply_order_status_change(
            $orderDAL,
            $orderId,
            $newOrderStatus,
            $_POST['tracking_id'] ?? null,
            $_POST['courier_company'] ?? null,
            true
        );

        if (lumak_admin_wants_json_response()) {
            lumak_admin_finish_json_request($result);
        }
    }

    $orderBefore = $orderDAL->getById($orderId);
    if ($orderBefore && !empty($newPaymentStatus) && $newPaymentStatus !== ($orderBefore['payment_status'] ?? '')) {
        $orderDAL->updatePaymentStatus($orderId, $newPaymentStatus);
        lumak_send_payment_status_notification($orderId, $newPaymentStatus);
    }

    header('Location: ' . BASE_URL . 'admin/order-detail.php?id=' . $orderId . ($fromParam ? '&from=' . urlencode($fromParam) : ''));
    exit;
}

$order = $orderDAL->getById($orderId);

if (!$order) {
    header('Location: ' . BASE_URL . 'admin/orders.php');
    exit;
}

$items = $orderDAL->getOrderItems($orderId);
$serialMap = $serialDAL->getMapByOrderId($orderId);
$canEditSerials = in_array($order['order_status'], ['pending', 'processing'], true);
$showSerialSection = !in_array($order['order_status'], ['cancelled'], true);
$flashMessage = $_GET['msg'] ?? '';
$flashType = $_GET['msgtype'] ?? '';
$adminName = $_SESSION['user_name'] ?? 'Admin';

// Set sidebar active state based on where we came from or order type
if ($from === 'whatsapp' || $order['payment_method'] === 'whatsapp') {
    $currentPage = 'whatsapp-orders';
    $backUrl = BASE_URL . 'admin/whatsapp-orders.php';
    $backLabel = 'Back to WhatsApp Orders';
} else {
    $currentPage = 'orders';
    $backUrl = BASE_URL . 'admin/orders.php';
    $backLabel = 'Back to Orders';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Order #<?php echo htmlspecialchars($order['order_number']); ?> - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .order-detail-grid { display: grid; grid-template-columns: 1fr 360px; gap: 24px; }
        .order-card { background: #fff; border: 1px solid #eee; border-radius: 14px; padding: 24px; margin-bottom: 20px; }
        .order-card h3 { font-size: 14px; font-weight: 600; color: #0d1137; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 8px; }
        .order-card h3 i { color: #e8600a; font-size: 14px; }
        .order-info-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 13px; border-bottom: 1px solid #f8f8f8; }
        .order-info-row:last-child { border-bottom: none; }
        .order-info-row span { color: #666; }
        .order-info-row strong { color: #0d1137; }
        .order-items-table { width: 100%; border-collapse: collapse; }
        .order-items-table th { background: #f8f9fc; padding: 10px 12px; font-size: 11px; font-weight: 600; color: #6b7099; text-align: left; text-transform: uppercase; }
        .order-items-table td { padding: 12px; font-size: 13px; border-bottom: 1px solid #f5f5f5; }
        .order-items-table tr:last-child td { border-bottom: none; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-badge.pending { background: #fff7ed; color: #ea580c; }
        .status-badge.processing { background: #eff6ff; color: #2563eb; }
        .status-badge.shipped { background: #f0fdf4; color: #16a34a; }
        .status-badge.delivered { background: #ecfdf5; color: #059669; }
        .status-badge.cancelled { background: #fef2f2; color: #ef4444; }
        .btn-invoice { display:inline-flex; align-items:center; gap:6px; padding:10px 16px; background:#D49A0A; color:#1a1a1a; border:none; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; font-family:'Poppins',sans-serif; text-decoration:none; }
        .btn-invoice.secondary { background:#FFE8A8; color:#D4A017; }
        .btn-invoice:hover { opacity:.92; }
        .status-badge.paid { background: #ecfdf5; color: #16a34a; }
        .status-badge.failed { background: #fef2f2; color: #ef4444; }
        .order-total-section { margin-top: 16px; padding-top: 16px; border-top: 1.5px solid #0d1137; }
        .total-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .total-row span { color: #666; }
        .total-row strong { color: #0d1137; }
        .total-row.grand { font-size: 16px; padding-top: 10px; }
        .total-row.grand strong { font-size: 20px; font-weight: 800; }
        .btn-print { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #0d1137; color: #fff; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; }
        .btn-print:hover { background: #1a1f4e; }
        .serial-grid { display: grid; gap: 12px; margin-top: 12px; }
        .serial-item {
            display: grid; grid-template-columns: 1fr 220px; gap: 12px; align-items: center;
            padding: 12px; background: #f8f9fc; border: 1px solid #e8ecf4; border-radius: 10px;
        }
        .serial-item label { font-size: 12px; font-weight: 600; color: #333; display: block; margin-bottom: 4px; }
        .serial-item small { font-size: 11px; color: #6b7099; }
        .serial-item input {
            width: 100%; padding: 9px 12px; border: 1.5px solid #e4e8f0; border-radius: 8px;
            font-family: 'Poppins', sans-serif; font-size: 13px;
        }
        .serial-item input:read-only { background: #eef1f7; color: #555; }
        .serial-save-btn {
            margin-top: 14px; padding: 10px 18px; background: #D4A017; color: #fff; border: none;
            border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif;
        }
        .serial-note { font-size: 12px; color: #6b7099; margin-top: 8px; line-height: 1.6; }
        @media (max-width: 768px) { .serial-item { grid-template-columns: 1fr; } }
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
                        <h1 class="page-title">Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
                        <p class="page-subtitle">Placed on <?php echo date('F d, Y \a\t h:i A', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <?php if (!empty($order['email'])): ?>
                        <button type="button" class="btn-invoice" onclick="sendInvoice(<?php echo $order['id']; ?>)"><i class="fas fa-paper-plane"></i> Email<?php echo (!empty($order['phone']) && ezymart_sms_is_configured()) ? ' + SMS' : ''; ?> Invoice</button>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>admin/invoice-download.php?order_id=<?php echo $order['id']; ?>" class="btn-invoice secondary" target="_blank"><i class="fas fa-file-pdf"></i> Download PDF</a>
                        <a href="<?php echo BASE_URL; ?>admin/invoice-preview.php?order_id=<?php echo $order['id']; ?>" class="btn-invoice secondary" target="_blank"><i class="fas fa-eye"></i> Preview</a>
                        <button class="btn-print" onclick="printLabel(<?php echo $order['id']; ?>)"><i class="fas fa-print"></i> Print Label</button>
                        <a href="<?php echo $backUrl; ?>" class="btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo $backLabel; ?></a>
                    </div>
                </div>

                <?php if ($flashMessage): ?>
                <div class="admin-toast <?php echo $flashType === 'success' ? 'success' : 'error'; ?>">
                    <i class="fas fa-<?php echo $flashType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($flashMessage); ?>
                </div>
                <?php endif; ?>

                <div class="order-detail-grid">
                    <!-- Left -->
                    <div>
                        <!-- Order Items -->
                        <div class="order-card">
                            <h3><i class="fas fa-box"></i> Order Items</h3>
                            <table class="order-items-table">
                                <thead>
                                    <tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                                        <td>LKR <?php echo number_format($item['price'], 0); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><strong>LKR <?php echo number_format($item['total'], 0); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="order-total-section">
                                <div class="total-row"><span>Subtotal</span><strong>LKR <?php echo number_format($order['subtotal'], 0); ?></strong></div>
                                <?php if ($order['discount'] > 0): ?>
                                <div class="total-row"><span>Discount<?php echo $order['coupon_code'] ? ' (' . $order['coupon_code'] . ')' : ''; ?></span><strong style="color:#16a34a;">- LKR <?php echo number_format($order['discount'], 0); ?></strong></div>
                                <?php endif; ?>
                                <div class="total-row"><span>Delivery Fee</span><strong><?php echo $order['delivery_fee'] == 0 ? 'FREE' : 'LKR ' . number_format($order['delivery_fee'], 0); ?></strong></div>
                                <div class="total-row grand"><span>Total</span><strong>LKR <?php echo number_format($order['total'], 0); ?></strong></div>
                            </div>
                        </div>

                        <?php if ($showSerialSection): ?>
                        <div class="order-card">
                            <h3><i class="fas fa-barcode"></i> Product Serial Numbers</h3>
                            <p class="serial-note">
                                Enter the physical serial number for each unit while the order is being processed.
                                These serials appear on the customer invoice and on the warranty check page.
                            </p>
                            <form method="POST" action="<?php echo BASE_URL; ?>admin/order-detail.php?id=<?php echo $order['id']; ?>">
                                <input type="hidden" name="save_serials" value="1">
                                <input type="hidden" name="from" value="<?php echo htmlspecialchars($from); ?>">
                                <div class="serial-grid">
                                    <?php foreach ($items as $item):
                                        $itemId = (int)$item['id'];
                                        $qty = max(1, (int)$item['quantity']);
                                        for ($unit = 1; $unit <= $qty; $unit++):
                                            $currentSerial = $serialMap[$itemId][$unit] ?? '';
                                    ?>
                                    <div class="serial-item">
                                        <div>
                                            <label><?php echo htmlspecialchars($item['product_name']); ?><?php echo $qty > 1 ? ' — Unit ' . $unit : ''; ?></label>
                                            <small>Qty <?php echo $qty; ?> · LKR <?php echo number_format($item['price'], 0); ?> each</small>
                                        </div>
                                        <div>
                                            <input type="text"
                                                name="serials[<?php echo $itemId; ?>][<?php echo $unit; ?>]"
                                                value="<?php echo htmlspecialchars($currentSerial); ?>"
                                                placeholder="Enter serial number"
                                                <?php echo $canEditSerials ? '' : 'readonly'; ?>>
                                        </div>
                                    </div>
                                    <?php endfor; endforeach; ?>
                                </div>
                                <?php if ($canEditSerials): ?>
                                <button type="submit" class="serial-save-btn"><i class="fas fa-save"></i> Save Serial Numbers</button>
                                <?php else: ?>
                                <p class="serial-note">Serial numbers can only be edited while the order is Pending or Processing.</p>
                                <?php endif; ?>
                            </form>
                        </div>
                        <?php endif; ?>

                        <!-- Delivery Address -->
                        <div class="order-card">
                            <h3><i class="fas fa-map-marker-alt"></i> Delivery Address</h3>
                            <p style="font-size:13px; color:#333; line-height:1.8;">
                                <strong><?php echo htmlspecialchars($order['full_name']); ?></strong><br>
                                <?php echo htmlspecialchars($order['address_1']); ?><br>
                                <?php if ($order['address_2']): echo htmlspecialchars($order['address_2']) . '<br>'; endif; ?>
                                <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['district']); ?>
                                <?php if ($order['postal_code']): ?> - <?php echo htmlspecialchars($order['postal_code']); ?><?php endif; ?><br>
                                <i class="fas fa-phone" style="color:#888;"></i> <?php echo htmlspecialchars($order['phone']); ?><br>
                                <i class="fas fa-envelope" style="color:#888;"></i> <?php echo htmlspecialchars($order['email']); ?>
                            </p>
                            <?php if ($order['notes']): ?>
                            <div style="margin-top:12px; padding:12px; background:#f8f9fc; border-radius:8px;">
                                <strong style="font-size:11px; color:#666;">Order Notes:</strong>
                                <p style="font-size:12px; color:#333; margin-top:4px;"><?php echo htmlspecialchars($order['notes']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right -->
                    <div>
                        <!-- Status -->
                        <div class="order-card">
                            <h3><i class="fas fa-info-circle"></i> Order Status</h3>
                            <form method="POST" action="<?php echo BASE_URL; ?>admin/order-detail.php?id=<?php echo $order['id']; ?>" id="orderStatusForm">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="from" value="<?php echo htmlspecialchars($from); ?>">
                                <div class="order-info-row" style="align-items:center;">
                                    <span>Order Status</span>
                                    <select name="order_status" id="orderStatusSelect" data-prev-status="<?php echo htmlspecialchars($order['order_status']); ?>" onchange="handleAdminOrderStatusChange(this, <?php echo (int)$order['id']; ?>)" style="padding:6px 12px; border:1.5px solid #e4e8f0; border-radius:8px; font-family:'Poppins',sans-serif; font-size:12px; font-weight:600; cursor:pointer; color:#0d1137; background:#f8f9fc;">
                                        <option value="pending" <?php echo $order['order_status']==='pending'?'selected':''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['order_status']==='processing'?'selected':''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order['order_status']==='shipped'?'selected':''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $order['order_status']==='delivered'?'selected':''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['order_status']==='cancelled'?'selected':''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="order-info-row" style="align-items:center;">
                                    <span>Payment Status</span>
                                    <select name="payment_status" onchange="this.form.submit()" style="padding:6px 12px; border:1.5px solid #e4e8f0; border-radius:8px; font-family:'Poppins',sans-serif; font-size:12px; font-weight:600; cursor:pointer; color:#0d1137; background:#f8f9fc;">
                                        <option value="pending" <?php echo $order['payment_status']==='pending'?'selected':''; ?>>Pending</option>
                                        <option value="paid" <?php echo $order['payment_status']==='paid'?'selected':''; ?>>Paid</option>
                                        <option value="failed" <?php echo $order['payment_status']==='failed'?'selected':''; ?>>Failed</option>
                                    </select>
                                </div>
                            </form>
                            <div class="order-info-row">
                                <span>Payment Method</span>
                                <strong><?php echo $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Card Payment'; ?></strong>
                            </div>
                            <div class="order-info-row">
                                <span>Delivery Method</span>
                                <strong><?php echo ucfirst($order['delivery_method']); ?></strong>
                            </div>
                            <div class="order-info-row">
                                <span>Points Earned</span>
                                <strong><?php echo $order['points_earned']; ?> pts</strong>
                            </div>
                            <?php if (!empty($order['courier_company']) || !empty($order['tracking_id'])): ?>
                            <div class="order-info-row"><span>Courier</span><strong><?php echo htmlspecialchars($order['courier_company'] ?? '—'); ?></strong></div>
                            <div class="order-info-row"><span>Tracking ID</span><strong><?php echo htmlspecialchars($order['tracking_id'] ?? '—'); ?></strong></div>
                            <?php endif; ?>
                        </div>

                        <!-- Customer Info -->
                        <div class="order-card">
                            <h3><i class="fas fa-user"></i> Customer</h3>
                            <div class="order-info-row"><span>Name</span><strong><?php echo htmlspecialchars($order['full_name']); ?></strong></div>
                            <div class="order-info-row"><span>Phone</span><strong><?php echo htmlspecialchars($order['phone']); ?></strong></div>
                            <div class="order-info-row"><span>Email</span><strong><?php echo htmlspecialchars($order['email']); ?></strong></div>
                            <div class="order-info-row"><span>Country</span><strong><?php
                                require_once __DIR__ . '/../dal/ShippingRatesDAL.php';
                                $shippingLabel = new ShippingRatesDAL();
                                echo htmlspecialchars($shippingLabel->getCountryLabel($order['shipping_country'] ?? 'LK'));
                            ?></strong></div>
                            <div class="order-info-row"><span><?php echo (($order['shipping_country'] ?? 'LK') === 'LK') ? 'District' : 'State / Province'; ?></span><strong><?php echo htmlspecialchars($order['district']); ?></strong></div>
                        </div>
                    </div>
                </div>

                <div class="admin-footer">
                    <p>&copy; 2026 ARIGATO.LK. All rights reserved.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.querySelector('.admin-layout').classList.toggle('sidebar-collapsed');
    });
    function printLabel(id) {
        window.open('<?php echo BASE_URL; ?>admin/print-label.php?id=' + id, '_blank', 'width=520,height=760');
    }

    window.submitAdminOrderStatus = function(orderId, value, shipping) {
        var formData = new FormData();
        formData.append('update_status', '1');
        formData.append('order_status', value);
        formData.append('from', <?php echo json_encode($from); ?>);
        if (shipping) {
            formData.append('tracking_id', shipping.tracking_id);
            formData.append('courier_company', shipping.courier_company);
        }

        return fetch('<?php echo BASE_URL; ?>admin/order-detail.php?id=' + orderId, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(r) { return r.json(); })
        .then(function(result) {
            if (result && result.success) {
                window.location.reload();
            }
            return result;
        });
    };
    function sendInvoice(orderId) {
        if (!confirm('Send invoice PDF to <?php echo htmlspecialchars($order['email'] ?? 'customer'); ?><?php echo (!empty($order['phone']) && ezymart_sms_is_configured()) ? ' and SMS link to ' . htmlspecialchars($order['phone']) : ''; ?>?')) return;
        var fd = new FormData();
        fd.append('order_id', orderId);
        fetch('<?php echo BASE_URL; ?>admin/send-invoice.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                alert(data.message || data.error || 'Done');
            })
            .catch(function() { alert('Failed to send invoice'); });
    }
    </script>
    <?php include __DIR__ . '/partials/shipping-overlay.php'; ?>
</body>
</html>
