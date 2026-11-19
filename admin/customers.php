<?php
/**
 * Admin Customers Management
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$message = '';
$messageType = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    if ($stmt->execute([':id' => $id])) {
        $message = 'Customer deleted successfully!';
        $messageType = 'success';
    } else {
        $message = 'Failed to delete customer.';
        $messageType = 'error';
    }
}

// Fetch all customers with order stats
$stmt = $conn->prepare("SELECT u.*, 
    (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
    (SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = u.id AND payment_status = 'paid') as total_spent,
    (SELECT phone FROM orders WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as order_phone
    FROM users u ORDER BY u.created_at DESC");
$stmt->execute();
$customers = $stmt->fetchAll();

$totalCustomers = count($customers);
$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Customers - Admin | <?php echo ADMIN_BRAND; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .dataTables_wrapper .dataTables_filter input { border: 1.5px solid #e4e8f0; border-radius: 8px; padding: 8px 14px; font-size: 13px; outline: none; font-family: 'Poppins', sans-serif; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: #2563eb; }
        .dataTables_wrapper .dataTables_length select { border: 1.5px solid #e4e8f0; border-radius: 8px; padding: 6px 10px; font-size: 13px; font-family: 'Poppins', sans-serif; }
        .dataTables_wrapper .dataTables_info { font-size: 12px; color: #6b7099; padding-top: 12px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 8px !important; font-size: 12px; padding: 6px 12px !important; margin: 0 2px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #2563eb !important; color: #fff !important; border-color: #2563eb !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #f0f2f8 !important; color: #0d1137 !important; }
        .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length { margin-bottom: 16px; font-size: 13px; }
        .dataTables_wrapper { padding: 20px; }

        .customer-avatar { width: 36px; height: 36px; border-radius: 50%; background: #001f52; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; }
        .customer-info { display: flex; align-items: center; gap: 12px; }
        .customer-info strong { font-size: 13px; color: #0d1137; display: block; }
        .customer-info small { color: #888; font-size: 11px; }
        .customer-stat { font-size: 12px; font-weight: 600; color: #0d1137; }
        .customer-stat small { display: block; font-size: 10px; color: #888; font-weight: 400; }
        .points-badge { display: inline-block; background: #fff7ed; color: #ea580c; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php $currentPage = 'customers'; include __DIR__ . '/partials/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/partials/topbar.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Customers</h1>
                        <p class="page-subtitle"><?php echo $totalCustomers; ?> registered customers</p>
                    </div>
                </div>

                <?php if ($message): ?>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var toast = document.createElement('div');
                    toast.style.cssText = 'position:fixed; bottom:30px; right:30px; z-index:9999; background:#fff; color:<?php echo $messageType === "success" ? "#16a34a" : "#dc2626"; ?>; padding:14px 20px; border-radius:12px; font-size:13px; display:flex; align-items:center; gap:10px; box-shadow:0 8px 30px rgba(0,0,0,0.12); border-left:4px solid <?php echo $messageType === "success" ? "#16a34a" : "#dc2626"; ?>; animation:slideInRight 0.4s ease, fadeOutRight 0.5s ease 4s forwards; font-family:Poppins,sans-serif;';
                    toast.innerHTML = '<i class="fas fa-<?php echo $messageType === "success" ? "check-circle" : "exclamation-circle"; ?>"></i> <?php echo htmlspecialchars($message); ?>';
                    document.body.appendChild(toast);
                    setTimeout(function() { toast.remove(); }, 5000);
                });
                </script>
                <?php endif; ?>

                <!-- Customers Table -->
                <div class="table-card">
                    <table class="data-table" id="customersTable">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Points</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                            <tr><td colspan="7" class="text-center">No customers yet.</td></tr>
                            <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-avatar"><?php echo strtoupper(substr($customer['name'], 0, 1)); ?></div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                                            <small><?php echo htmlspecialchars($customer['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><span style="font-size:12px; color:#555;"><?php echo htmlspecialchars($customer['phone'] ?? $customer['order_phone'] ?? '-'); ?></span></td>
                                <td>
                                    <div class="customer-stat">
                                        <?php echo (int)$customer['total_orders']; ?>
                                        <small>orders</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="customer-stat">
                                        LKR <?php echo number_format($customer['total_spent'], 0); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="points-badge"><?php echo (int)($customer['points'] ?? 0); ?> pts</span>
                                </td>
                                <td><small style="color:#888;"><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></small></td>
                                <td class="actions">
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this customer? All their orders will also be deleted.')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
                                        <button type="submit" class="btn-action delete" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
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
        $('#customersTable').DataTable({
            pageLength: 10,
            order: [[5, 'desc']],
            columnDefs: [{ orderable: false, targets: [6] }],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ customers",
                paginate: { previous: "‹", next: "›" }
            }
        });
    });
    </script>
    <style>
    @keyframes slideInRight { from { opacity:0; transform:translateX(100%); } to { opacity:1; transform:translateX(0); } }
    @keyframes fadeOutRight { from { opacity:1; transform:translateX(0); } to { opacity:0; transform:translateX(100%); } }
    </style>
</body>
</html>
