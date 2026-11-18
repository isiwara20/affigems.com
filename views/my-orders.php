<?php
$pageTitle = "My Orders - ARIGATO.LK";
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/OrderDAL.php';

// Redirect if not logged in - BEFORE any output
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

require_once __DIR__ . '/partials/header.php';

$userId = $_SESSION['user_id'];
$orderDAL = new OrderDAL();
$orders = $orderDAL->getByUser($userId);

// Get order items for detail view
$selectedOrder = null;
$selectedItems = [];
if (isset($_GET['id'])) {
    $selectedOrder = $orderDAL->getById((int)$_GET['id']);
    if ($selectedOrder && $selectedOrder['user_id'] == $userId) {
        $selectedItems = $orderDAL->getOrderItems($selectedOrder['id']);
    } else {
        $selectedOrder = null;
    }
}
?>

<!-- Breadcrumb -->
<div class="breadcrumb-section">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>">Home</a>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <a href="<?php echo BASE_URL; ?>profile.php">My Account</a>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="breadcrumb-current">My Orders</span>
        </nav>
    </div>
</div>

<section class="myorders-section">
    <div class="container">
        <div class="profile-page-header" style="display:flex; align-items:center; justify-content:space-between;">
            <div>
                <h1>My Orders</h1>
                <p>Track and manage your orders</p>
            </div>
            <?php if ($selectedOrder): ?>
            <a href="<?php echo BASE_URL; ?>my-orders.php" class="myorders-back"><i class="fas fa-arrow-left"></i> Back to Orders</a>
            <?php endif; ?>
        </div>

        <div class="profile-layout">
            <!-- Left Sidebar -->
            <aside class="profile-sidebar">
                <div class="profile-sidebar-card">
                    <h3 class="profile-sidebar-title">My Account</h3>
                    <nav class="profile-nav">
                        <a href="<?php echo BASE_URL; ?>profile.php" class="profile-nav-item">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="<?php echo BASE_URL; ?>my-orders.php" class="profile-nav-item active">
                            <i class="fas fa-box"></i> My Orders
                        </a>
                        <a href="<?php echo BASE_URL; ?>my-warranty-claims.php" class="profile-nav-item">
                            <i class="fas fa-shield-alt"></i> Warranty Claims
                        </a>
                        <a href="<?php echo BASE_URL; ?>wishlist.php" class="profile-nav-item">
                            <i class="far fa-heart"></i> Wishlist
                        </a>
                        <a href="<?php echo BASE_URL; ?>profile.php?section=address" class="profile-nav-item">
                            <i class="fas fa-map-marker-alt"></i> Address Book
                        </a>
                        <a href="<?php echo BASE_URL; ?>profile.php?section=payment" class="profile-nav-item">
                            <i class="fas fa-credit-card"></i> Payment Methods
                        </a>
                        <a href="<?php echo BASE_URL; ?>profile.php?section=password" class="profile-nav-item">
                            <i class="fas fa-lock"></i> Change Password
                        </a>
                        <a href="<?php echo BASE_URL; ?>profile.php?section=notifications" class="profile-nav-item">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                        <a href="#" onclick="showLogoutConfirm(event)" class="profile-nav-item">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </nav>
                </div>

                <div class="profile-sidebar-help">
                    <h4>Need Help?</h4>
                    <p>We're here to help you</p>
                    <a href="#" class="profile-support-btn" onclick="event.preventDefault(); toggleChatbot();">
                        <i class="fas fa-headset"></i> Contact Support
                    </a>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="profile-main">

        <?php if ($selectedOrder): ?>
        <!-- Order Detail View -->
        <div class="myorders-detail">

            <div class="myorders-detail-header">
                <div>
                    <h2>Order #<?php echo htmlspecialchars($selectedOrder['order_number']); ?></h2>
                    <p>Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($selectedOrder['created_at'])); ?></p>
                </div>
                <span class="myorders-status-badge status-<?php echo $selectedOrder['order_status']; ?>">
                    <?php echo ucfirst($selectedOrder['order_status']); ?>
                </span>
            </div>

            <!-- Progress Tracker -->
            <?php
            $statusSteps = ['pending', 'processing', 'shipped', 'delivered'];
            $currentStep = array_search($selectedOrder['order_status'], $statusSteps);
            if ($selectedOrder['order_status'] === 'cancelled') $currentStep = -1;
            ?>
            <?php if ($selectedOrder['order_status'] !== 'cancelled'): ?>
            <div class="myorders-progress">
                <?php foreach ($statusSteps as $idx => $step): ?>
                <div class="progress-step <?php echo $idx <= $currentStep ? 'completed' : ''; ?> <?php echo $idx === $currentStep ? 'current' : ''; ?>">
                    <div class="progress-icon">
                        <?php if ($idx < $currentStep): ?>
                        <i class="fas fa-check"></i>
                        <?php else: ?>
                        <span><?php echo $idx + 1; ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="progress-label"><?php echo ucfirst($step); ?></span>
                </div>
                <?php if ($idx < count($statusSteps) - 1): ?>
                <div class="progress-line <?php echo $idx < $currentStep ? 'completed' : ''; ?>"></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="myorders-cancelled-notice">
                <i class="fas fa-times-circle"></i> This order has been cancelled.
            </div>
            <?php endif; ?>

            <!-- Order Items -->
            <div class="myorders-items-card">
                <h3><i class="fas fa-box"></i> Items Ordered</h3>
                <div class="myorders-items-list">
                    <?php foreach ($selectedItems as $item): ?>
                    <div class="myorders-item">
                        <div class="myorders-item-info">
                            <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                            <span>Qty: <?php echo $item['quantity']; ?> &#215; <?php echo format_price($item['price']); ?></span>
                        </div>
                        <span class="myorders-item-total"><?php echo format_price($item['total']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="myorders-totals">
                    <div class="myorders-total-row"><span>Subtotal</span><strong><?php echo format_price($selectedOrder['subtotal']); ?></strong></div>
                    <?php if ($selectedOrder['discount'] > 0): ?>
                    <div class="myorders-total-row discount"><span>Discount</span><strong>- <?php echo format_price($selectedOrder['discount']); ?></strong></div>
                    <?php endif; ?>
                    <div class="myorders-total-row"><span>Delivery</span><strong><?php echo $selectedOrder['delivery_fee'] == 0 ? 'FREE' : format_price($selectedOrder['delivery_fee']); ?></strong></div>
                    <div class="myorders-total-row grand"><span>Total</span><strong><?php echo format_price($selectedOrder['total']); ?></strong></div>
                </div>
            </div>

            <!-- Delivery & Payment Info -->
            <div class="myorders-info-grid">
                <div class="myorders-info-card">
                    <h3><i class="fas fa-truck"></i> Delivery Details</h3>
                    <p><strong><?php echo htmlspecialchars($selectedOrder['full_name']); ?></strong></p>
                    <p><?php echo htmlspecialchars($selectedOrder['address_1']); ?></p>
                    <?php if ($selectedOrder['address_2']): ?><p><?php echo htmlspecialchars($selectedOrder['address_2']); ?></p><?php endif; ?>
                    <p><?php echo htmlspecialchars($selectedOrder['city']); ?>, <?php echo htmlspecialchars($selectedOrder['district']); ?> <?php echo htmlspecialchars($selectedOrder['postal_code']); ?></p>
                    <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($selectedOrder['phone']); ?></p>
                </div>
                <div class="myorders-info-card">
                    <h3><i class="fas fa-credit-card"></i> Payment Info</h3>
                    <p><strong>Method:</strong> <?php echo $selectedOrder['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Card Payment'; ?></p>
                    <p><strong>Status:</strong> <span class="myorders-pay-badge pay-<?php echo $selectedOrder['payment_status']; ?>"><?php echo ucfirst($selectedOrder['payment_status']); ?></span></p>
                    <p><strong>Delivery:</strong> <?php echo ucfirst($selectedOrder['delivery_method']); ?></p>
                    <?php if ($selectedOrder['points_earned'] > 0): ?>
                    <p><strong>Points Earned:</strong> <?php echo $selectedOrder['points_earned']; ?> pts</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Orders List View -->
        <?php if (empty($orders)): ?>
        <div class="myorders-empty">
            <div class="myorders-empty-icon"><i class="fas fa-shopping-bag"></i></div>
            <h3>No orders yet</h3>
            <p>When you place an order, it will appear here.</p>
            <a href="<?php echo BASE_URL; ?>shop.php" class="myorders-shop-btn"><i class="fas fa-store"></i> Start Shopping</a>
        </div>
        <?php else: ?>
        <div class="myorders-list">
            <?php foreach ($orders as $order): ?>
            <a href="<?php echo BASE_URL; ?>my-orders.php?id=<?php echo $order['id']; ?>" class="myorders-card">
                <div class="myorders-card-top">
                    <div class="myorders-card-order">
                        <span class="myorders-card-number">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                        <span class="myorders-card-date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                    </div>
                    <span class="myorders-status-badge status-<?php echo $order['order_status']; ?>">
                        <?php echo ucfirst($order['order_status']); ?>
                    </span>
                </div>
                <div class="myorders-card-body">
                    <div class="myorders-card-items">
                        <span><?php 
                        $itemCount = 0;
                        try { $items = $orderDAL->getOrderItems($order['id']); $itemCount = count($items); } catch(Exception $e) {}
                        echo $itemCount . ' item' . ($itemCount !== 1 ? 's' : '');
                        ?></span>
                        <span class="myorders-card-method">
                            <i class="fas fa-<?php echo $order['payment_method'] === 'cod' ? 'money-bill-wave' : 'credit-card'; ?>"></i>
                            <?php echo $order['payment_method'] === 'cod' ? 'COD' : 'Card'; ?>
                        </span>
                    </div>
                    <span class="myorders-card-total"><?php echo format_price($order['total']); ?></span>
                </div>
                <div class="myorders-card-footer">
                    <span class="myorders-card-view">View Details <i class="fas fa-chevron-right"></i></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
