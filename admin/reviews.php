<?php
/**
 * Admin Reviews Management
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/ReviewDAL.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$reviewDAL = new ReviewDAL();
$message = '';
$messageType = '';
$filter = trim((string)($_GET['status'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = (int)$_POST['id'];
    $action = (string)$_POST['action'];

    if ($action === 'approve' && $reviewDAL->approve($id)) {
        $message = 'Review approved and is now visible on the product page.';
        $messageType = 'success';
    } elseif ($action === 'reject' && $reviewDAL->reject($id)) {
        $message = 'Review rejected and hidden from the product page.';
        $messageType = 'success';
    } elseif ($action === 'delete' && $reviewDAL->delete($id)) {
        $message = 'Review deleted successfully.';
        $messageType = 'success';
    } else {
        $message = 'Could not update the review. Please try again.';
        $messageType = 'error';
    }
}

$reviews = $reviewDAL->getAll($filter !== '' ? $filter : null);
$pendingCount = $reviewDAL->getPendingCount();
$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Reviews - Admin | <?php echo ADMIN_BRAND; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .review-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
        .review-filter-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: 999px; border: 1px solid #e4e8f0;
            background: #fff; color: #4b5563; font-size: 12px; font-weight: 600;
            text-decoration: none; transition: all 0.2s;
        }
        .review-filter-btn:hover { border-color: #0d1137; color: #0d1137; }
        .review-filter-btn.active { background: #0d1137; border-color: #0d1137; color: #fff; }
        .review-filter-btn .count {
            min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px;
            background: rgba(0,0,0,0.08); font-size: 10px; display: inline-flex;
            align-items: center; justify-content: center;
        }
        .review-filter-btn.active .count { background: rgba(255,255,255,0.18); }
        .status-badge {
            padding: 4px 10px; border-radius: 20px; font-size: 10px;
            font-weight: 600; display: inline-block; text-transform: capitalize;
        }
        .status-badge.pending { background: #fff7ed; color: #ea580c; }
        .status-badge.approved { background: #ecfdf5; color: #059669; }
        .status-badge.rejected { background: #fef2f2; color: #ef4444; }
        .review-actions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .btn-action.approve { background: #ecfdf5; color: #059669; }
        .btn-action.approve:hover { background: #059669; color: #fff; }
        .btn-action.reject { background: #fff7ed; color: #ea580c; }
        .btn-action.reject:hover { background: #ea580c; color: #fff; }
        tr.row-pending { background: #fffdf8; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php $currentPage = 'reviews'; include __DIR__ . '/partials/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/partials/topbar.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Customer Reviews</h1>
                        <p class="page-subtitle">Approve reviews before they appear on product pages</p>
                    </div>
                    <span class="badge-count" style="font-size:14px; padding:8px 16px;">
                        <?php echo count($reviews); ?> shown
                        <?php if ($pendingCount > 0): ?>
                            · <strong style="color:#ea580c;"><?php echo $pendingCount; ?> pending</strong>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <div class="review-filters">
                    <a href="<?php echo BASE_URL; ?>admin/reviews.php" class="review-filter-btn <?php echo $filter === '' ? 'active' : ''; ?>">All</a>
                    <a href="<?php echo BASE_URL; ?>admin/reviews.php?status=pending" class="review-filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                        Pending <?php if ($pendingCount > 0): ?><span class="count"><?php echo $pendingCount; ?></span><?php endif; ?>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/reviews.php?status=approved" class="review-filter-btn <?php echo $filter === 'approved' ? 'active' : ''; ?>">Approved</a>
                    <a href="<?php echo BASE_URL; ?>admin/reviews.php?status=rejected" class="review-filter-btn <?php echo $filter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
                </div>

                <div class="table-card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reviews)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No reviews found.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($reviews as $review):
                                $status = $review['status'] ?? 'pending';
                            ?>
                            <tr class="<?php echo $status === 'pending' ? 'row-pending' : ''; ?>">
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div style="width:34px; height:34px; background:#001f52; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:12px; font-weight:700;">
                                            <?php echo strtoupper(substr($review['user_name'], 0, 1)); ?>
                                        </div>
                                        <strong style="font-size:13px;"><?php echo htmlspecialchars($review['user_name']); ?></strong>
                                    </div>
                                </td>
                                <td style="max-width:200px;">
                                    <span style="font-size:12px; color:#333;"><?php echo htmlspecialchars($review['product_name'] ?? 'Deleted Product'); ?></span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:4px;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star" style="font-size:11px; color:<?php echo $i <= $review['rating'] ? '#f5a623' : '#ddd'; ?>;"></i>
                                        <?php endfor; ?>
                                        <span style="font-size:12px; color:#666; margin-left:4px;">(<?php echo $review['rating']; ?>)</span>
                                    </div>
                                </td>
                                <td style="max-width:300px;">
                                    <p style="font-size:12px; color:#555; line-height:1.5; margin:0; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                        <?php echo htmlspecialchars($review['comment']); ?>
                                    </p>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></span>
                                </td>
                                <td>
                                    <span style="font-size:12px; color:#999;"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                </td>
                                <td>
                                    <div class="review-actions">
                                        <?php if ($status !== 'approved'): ?>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" class="btn-action approve" title="Approve review">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>

                                        <?php if ($status !== 'rejected'): ?>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Reject this review? It will not be shown on the product page.')">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" class="btn-action reject" title="Reject review">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>

                                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this review permanently?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" class="btn-action delete" title="Delete review">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
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

    <script>
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.querySelector('.admin-layout').classList.toggle('sidebar-collapsed');
    });
    </script>
</body>
</html>
