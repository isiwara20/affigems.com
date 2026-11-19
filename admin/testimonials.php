<?php
/**
 * Admin Testimonials Management
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/TestimonialDAL.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$testimonialDAL = new TestimonialDAL();
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    if ($action === 'approve' && $id > 0) {
        $testimonialDAL->updateStatus($id, 'approved');
        $message = 'Testimonial approved!';
        $messageType = 'success';
    } elseif ($action === 'reject' && $id > 0) {
        $testimonialDAL->updateStatus($id, 'rejected');
        $message = 'Testimonial rejected.';
        $messageType = 'success';
    } elseif ($action === 'delete' && $id > 0) {
        $testimonialDAL->delete($id);
        $message = 'Testimonial deleted.';
        $messageType = 'success';
    }
}

$testimonials = $testimonialDAL->getAll();
$pendingCount = count(array_filter($testimonials, fn($t) => $t['status'] === 'pending'));
$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Testimonials - Admin | <?php echo ADMIN_BRAND; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .testimonial-card { background: #fff; border: 1px solid #eee; border-radius: 14px; padding: 22px; margin-bottom: 16px; transition: border-color 0.2s; }
        .testimonial-card:hover { border-color: #ddd; }
        .testimonial-card.pending { border-left: 4px solid #ea580c; }
        .testimonial-card.approved { border-left: 4px solid #16a34a; }
        .testimonial-card.rejected { border-left: 4px solid #ef4444; opacity: 0.6; }
        .testimonial-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .testimonial-user { display: flex; align-items: center; gap: 12px; }
        .testimonial-avatar { width: 38px; height: 38px; border-radius: 50%; background: #001f52; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; }
        .testimonial-name { font-size: 14px; font-weight: 600; color: #0d1137; }
        .testimonial-meta { font-size: 11px; color: #888; }
        .testimonial-stars { color: #f5a623; font-size: 13px; margin-bottom: 10px; }
        .testimonial-message { font-size: 13px; color: #444; line-height: 1.7; margin-bottom: 14px; background: #f8f9fc; padding: 14px; border-radius: 8px; }
        .testimonial-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .testimonial-actions form { display: inline; }
        .btn-approve { padding: 7px 16px; background: #ecfdf5; color: #16a34a; border: 1px solid #bbf7d0; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; }
        .btn-approve:hover { background: #16a34a; color: #fff; border-color: #16a34a; }
        .btn-reject { padding: 7px 16px; background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; }
        .btn-reject:hover { background: #ea580c; color: #fff; border-color: #ea580c; }
        .btn-delete-t { padding: 7px 16px; background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; }
        .btn-delete-t:hover { background: #ef4444; color: #fff; border-color: #ef4444; }
        .status-tag { font-size: 10px; font-weight: 600; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.3px; }
        .status-tag.pending { background: #fff7ed; color: #ea580c; }
        .status-tag.approved { background: #ecfdf5; color: #16a34a; }
        .status-tag.rejected { background: #fef2f2; color: #ef4444; }
        .pending-badge { background: #ea580c; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; margin-left: 8px; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php $currentPage = 'testimonials'; include __DIR__ . '/partials/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/partials/topbar.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Testimonials <?php if ($pendingCount > 0): ?><span class="pending-badge"><?php echo $pendingCount; ?> pending</span><?php endif; ?></h1>
                        <p class="page-subtitle">Manage customer service reviews</p>
                    </div>
                </div>

                <?php if ($message): ?>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var toast = document.createElement('div');
                    toast.style.cssText = 'position:fixed; bottom:30px; right:30px; z-index:9999; background:#fff; color:<?php echo $messageType === "success" ? "#16a34a" : "#dc2626"; ?>; padding:14px 20px; border-radius:12px; font-size:13px; display:flex; align-items:center; gap:10px; box-shadow:0 8px 30px rgba(0,0,0,0.12); border-left:4px solid <?php echo $messageType === "success" ? "#16a34a" : "#dc2626"; ?>; animation:slideInRight 0.4s ease, fadeOutRight 0.5s ease 4s forwards; font-family:Poppins,sans-serif;';
                    toast.innerHTML = '<i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>';
                    document.body.appendChild(toast);
                    setTimeout(function() { toast.remove(); }, 5000);
                });
                </script>
                <?php endif; ?>

                <?php if (empty($testimonials)): ?>
                <div style="text-align:center; padding:60px; color:#888;">
                    <i class="fas fa-quote-left" style="font-size:3rem; color:#ddd; margin-bottom:16px; display:block;"></i>
                    <h3 style="color:#0d1137; margin-bottom:6px;">No testimonials yet</h3>
                    <p style="font-size:13px;">Customer testimonials will appear here once submitted.</p>
                </div>
                <?php else: ?>
                <?php foreach ($testimonials as $t): ?>
                <div class="testimonial-card <?php echo $t['status']; ?>">
                    <div class="testimonial-header">
                        <div class="testimonial-user">
                            <div class="testimonial-avatar"><?php echo strtoupper(substr($t['name'], 0, 1)); ?></div>
                            <div>
                                <div class="testimonial-name"><?php echo htmlspecialchars($t['name']); ?></div>
                                <div class="testimonial-meta">
                                    <?php echo htmlspecialchars($t['email']); ?> 
                                    <?php if ($t['order_number']): ?>· Order #<?php echo htmlspecialchars($t['order_number']); ?><?php endif; ?>
                                    · <?php echo date('M d, Y', strtotime($t['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        <span class="status-tag <?php echo $t['status']; ?>"><?php echo ucfirst($t['status']); ?></span>
                    </div>
                    <div class="testimonial-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star" style="color:<?php echo $i <= $t['rating'] ? '#f5a623' : '#ddd'; ?>;"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="testimonial-message">"<?php echo nl2br(htmlspecialchars($t['message'])); ?>"</div>
                    <div class="testimonial-actions">
                        <?php if ($t['status'] !== 'approved'): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                            <button type="submit" class="btn-approve"><i class="fas fa-check"></i> Approve</button>
                        </form>
                        <?php endif; ?>
                        <?php if ($t['status'] !== 'rejected'): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                            <button type="submit" class="btn-reject"><i class="fas fa-times"></i> Reject</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" onsubmit="return confirm('Delete this testimonial permanently?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                            <button type="submit" class="btn-delete-t"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <div class="admin-footer">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo ADMIN_BRAND; ?>. All rights reserved. Developed by Chandila.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.querySelector('.admin-layout').classList.toggle('sidebar-collapsed');
    });
    </script>
    <style>
    @keyframes slideInRight { from { opacity:0; transform:translateX(100%); } to { opacity:1; transform:translateX(0); } }
    @keyframes fadeOutRight { from { opacity:1; transform:translateX(0); } to { opacity:0; transform:translateX(100%); } }
    </style>
</body>
</html>
