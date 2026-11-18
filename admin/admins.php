<?php
$currentPage = 'admins';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/AdminDAL.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$adminDAL = new AdminDAL();
$adminName = $_SESSION['user_name'] ?? 'Admin';
$message = '';
$messageType = '';
$error = '';

// Handle add admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_admin') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($adminDAL->findByEmail($email)) {
            $error = 'An admin with this email already exists.';
        } else {
            $adminDAL->create($name, $email, $password);
            $message = 'Admin added successfully!';
            $messageType = 'success';
        }
    } elseif ($action === 'delete_admin') {
        $deleteId = (int)($_POST['admin_id'] ?? 0);
        if ($deleteId > 0 && $adminDAL->getCount() > 1) {
            $adminDAL->delete($deleteId);
            $message = 'Admin removed.';
            $messageType = 'success';
        } else {
            $error = 'Cannot delete the last admin.';
        }
    }
}

$admins = $adminDAL->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Manage Admins - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .admin-card { background:#fff; border:1px solid #eee; border-radius:14px; padding:20px; display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; transition:all 0.2s; }
        .admin-card:hover { border-color:#2563eb; }
        .admin-card-left { display:flex; align-items:center; gap:14px; }
        .admin-card-avatar { width:44px; height:44px; background:#8A6508; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:16px; font-weight:700; }
        .admin-card-info h4 { font-size:14px; font-weight:600; color:#0d1137; margin-bottom:2px; }
        .admin-card-info p { font-size:12px; color:#6b7099; margin:0; }
        .admin-card-info .admin-date { font-size:10px; color:#aaa; margin-top:2px; }
        .btn-delete-admin { padding:8px 14px; background:#fef2f2; color:#ef4444; border:none; border-radius:8px; font-size:11px; font-weight:600; cursor:pointer; font-family:'Poppins',sans-serif; transition:all 0.2s; }
        .btn-delete-admin:hover { background:#ef4444; color:#fff; }
        .admin-you-badge { font-size:9px; padding:2px 8px; background:#ecfdf5; color:#059669; border-radius:10px; font-weight:600; margin-left:8px; }
        .add-admin-form { background:#fff; border:1px solid #eee; border-radius:14px; padding:28px; margin-bottom:24px; }
        .add-admin-form h3 { font-size:15px; font-weight:700; color:#0d1137; margin-bottom:18px; display:flex; align-items:center; gap:8px; }
        .add-admin-form h3 i { color:#e8600a; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; align-items:end; }
        .form-grid .form-group label { display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px; }
        .form-grid .form-group input { width:100%; padding:11px 14px; border:1.5px solid #e4e8f0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; outline:none; }
        .form-grid .form-group input:focus { border-color:#2563eb; }
        .btn-add-admin { padding:11px 24px; background:#8A6508; color:#fff; border:none; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; height:44px; }
        .btn-add-admin:hover { background:#1a1f4e; }
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
                        <h1 class="page-title">Manage Admins</h1>
                        <p class="page-subtitle">Add or remove administrator accounts</p>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="admin-toast success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="admin-toast error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <!-- Add Admin Form -->
                <div class="add-admin-form">
                    <h3><i class="fas fa-user-plus"></i> Add New Admin</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_admin">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="name" placeholder="Admin name" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" placeholder="admin@example.com" required>
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" placeholder="Min 6 characters" minlength="6" required>
                            </div>
                        </div>
                        <div style="margin-top:16px;">
                            <button type="submit" class="btn-add-admin"><i class="fas fa-plus"></i> Add Admin</button>
                        </div>
                    </form>
                </div>

                <!-- Admin List -->
                <h3 style="font-size:14px; font-weight:600; color:#0d1137; margin-bottom:14px;">Current Admins (<?php echo count($admins); ?>)</h3>

                <?php foreach ($admins as $admin): ?>
                <div class="admin-card">
                    <div class="admin-card-left">
                        <div class="admin-card-avatar"><?php echo strtoupper(substr($admin['name'], 0, 1)); ?></div>
                        <div class="admin-card-info">
                            <h4>
                                <?php echo htmlspecialchars($admin['name']); ?>
                                <?php if ($admin['email'] === ($_SESSION['user_email'] ?? '')): ?>
                                <span class="admin-you-badge">You</span>
                                <?php endif; ?>
                            </h4>
                            <p><?php echo htmlspecialchars($admin['email']); ?></p>
                            <span class="admin-date">Added <?php echo date('M d, Y', strtotime($admin['created_at'])); ?></span>
                        </div>
                    </div>
                    <?php if ($admin['email'] !== ($_SESSION['user_email'] ?? '') && count($admins) > 1): ?>
                    <form method="POST" onsubmit="return confirm('Remove this admin?');">
                        <input type="hidden" name="action" value="delete_admin">
                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                        <button type="submit" class="btn-delete-admin"><i class="fas fa-trash-alt"></i> Remove</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <div class="admin-footer" style="margin-top:40px;">
                    <p>&copy; 2026 ARIGATO.LK. All rights reserved.</p>
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
