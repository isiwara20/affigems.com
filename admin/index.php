<?php
/**
 * Admin Dashboard - Dynamic Data
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/OrderDAL.php';
require_once __DIR__ . '/../dal/ProductDAL.php';
require_once __DIR__ . '/../dal/CategoryDAL.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
$currentPage = 'dashboard';

// ---- Fetch Real Data ----
$orderDAL = new OrderDAL();
$productDAL = new ProductDAL();
$categoryDAL = new CategoryDAL();

// All orders
$allOrders = $orderDAL->getAll();
$totalOrders = count($allOrders);

// Revenue (sum of paid orders total)
$totalRevenue = 0;
foreach ($allOrders as $o) {
    if ($o['payment_status'] === 'paid' || $o['payment_method'] === 'cod') {
        $totalRevenue += $o['total'];
    }
}

// Customer count
$db = new Database();
$conn = $db->getConnection();
$totalCustomers = 0;
$newCustomers30d = 0;
try {
    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM users");
    $totalCustomers = (int)$stmt->fetch()['cnt'];
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM users WHERE created_at >= :d");
    $stmt->execute([':d' => date('Y-m-d', strtotime('-30 days'))]);
    $newCustomers30d = (int)$stmt->fetch()['cnt'];
} catch (Exception $e) {}

// Products & Categories
$allProducts = $productDAL->getAll();
$totalProducts = count($allProducts);
$allCategories = $categoryDAL->getAll();
$totalCategories = count($allCategories);

// Orders by status
$completedOrders = count(array_filter($allOrders, fn($o) => $o['order_status'] === 'delivered'));
$processingOrders = count(array_filter($allOrders, fn($o) => $o['order_status'] === 'processing'));
$pendingOrders = count(array_filter($allOrders, fn($o) => $o['order_status'] === 'pending'));
$cancelledOrders = count(array_filter($allOrders, fn($o) => $o['order_status'] === 'cancelled'));
$shippedOrders = count(array_filter($allOrders, fn($o) => $o['order_status'] === 'shipped'));

// Percentages for donut
$completedPct = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100) : 0;
$processingPct = $totalOrders > 0 ? round(($processingOrders / $totalOrders) * 100) : 0;
$pendingPct = $totalOrders > 0 ? round(($pendingOrders / $totalOrders) * 100) : 0;
$cancelledPct = $totalOrders > 0 ? round(($cancelledOrders / $totalOrders) * 100) : 0;

// Recent 5 orders
$recentOrders = array_slice($allOrders, 0, 5);

// Revenue by week (last 6 weeks)
$weeklyRevenue = [];
for ($i = 5; $i >= 0; $i--) {
    $weekStart = date('Y-m-d', strtotime("-{$i} weeks monday"));
    $weekEnd = date('Y-m-d', strtotime("-{$i} weeks sunday"));
    $weekLabel = date('M d', strtotime($weekStart));
    $weekTotal = 0;
    foreach ($allOrders as $o) {
        $orderDate = date('Y-m-d', strtotime($o['created_at']));
        if ($orderDate >= $weekStart && $orderDate <= $weekEnd && ($o['payment_status'] === 'paid' || $o['payment_method'] === 'cod')) {
            $weekTotal += $o['total'];
        }
    }
    $weeklyRevenue[] = ['label' => $weekLabel, 'total' => $weekTotal];
}
$maxWeekRevenue = max(array_column($weeklyRevenue, 'total') ?: [1]);

// Top selling products (from order_items)
$topProducts = [];
try {
    $stmt = $conn->query("SELECT oi.product_name as name, oi.product_id, SUM(oi.quantity) as total_sold, SUM(oi.total) as revenue,
        SUM(oi.total - (oi.quantity * COALESCE(p.cost_price, 0))) as net_profit
        FROM order_items oi
        LEFT JOIN products p ON p.id = oi.product_id
        GROUP BY oi.product_id, oi.product_name
        ORDER BY total_sold DESC LIMIT 5");
    $topProducts = $stmt->fetchAll();
} catch (Exception $e) {}

// Product profit margins (selling price - cost price)
$productProfits = [];
$totalCatalogProfit = 0;
foreach ($allProducts as $p) {
    $sellPrice = (float)$p['price'];
    $costPrice = isset($p['cost_price']) && $p['cost_price'] !== null && $p['cost_price'] !== '' ? (float)$p['cost_price'] : null;
    if ($costPrice === null) {
        continue;
    }
    $netProfit = $sellPrice - $costPrice;
    $productProfits[] = [
        'id' => $p['id'],
        'name' => $p['name'],
        'sell_price' => $sellPrice,
        'cost_price' => $costPrice,
        'net_profit' => $netProfit,
        'margin_pct' => $sellPrice > 0 ? round(($netProfit / $sellPrice) * 100, 1) : 0,
    ];
    $totalCatalogProfit += $netProfit;
}
usort($productProfits, fn($a, $b) => $b['net_profit'] <=> $a['net_profit']);

// Total net profit from paid/COD orders
$totalNetProfit = 0;
try {
    $stmt = $conn->query("SELECT SUM(oi.total - (oi.quantity * COALESCE(p.cost_price, 0))) as net_profit
        FROM order_items oi
        INNER JOIN orders o ON o.id = oi.order_id
        LEFT JOIN products p ON p.id = oi.product_id
        WHERE o.payment_status = 'paid' OR o.payment_method = 'cod'");
    $totalNetProfit = (float)($stmt->fetch()['net_profit'] ?? 0);
} catch (Exception $e) {}

// Low stock products (stock < threshold)
$lowStockThreshold = 20;
try {
    require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
    $settingsDAL = new SiteSettingsDAL();
    $lowStockThreshold = (int)$settingsDAL->get('low_stock_threshold', '5');
} catch (Exception $e) {}
$lowStockProducts = array_filter($allProducts, fn($p) => (int)$p['stock'] <= $lowStockThreshold && (int)$p['stock'] > 0);
$lowStockProducts = array_slice($lowStockProducts, 0, 5);

// Date range display
$dateFrom = date('M d, Y', strtotime('-30 days'));
$dateTo = date('M d, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Admin Dashboard - <?php echo ADMIN_BRAND; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <?php require_once __DIR__ . '/partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php require_once __DIR__ . '/partials/topbar.php'; ?>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <div>
                        <p class="welcome-text">Welcome back,</p>
                        <h1 class="welcome-name"><?php echo htmlspecialchars($adminName); ?> <span class="admin-badge">Admin</span></h1>
                    </div>
                    <div class="date-filter">
                        <i class="far fa-calendar-alt"></i>
                        <span><?php echo $dateFrom; ?> - <?php echo $dateTo; ?></span>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-shopping-bag"></i></div>
                        <div class="stat-info">
                            <p class="stat-label">Total Orders</p>
                            <h3 class="stat-value"><?php echo number_format($totalOrders); ?></h3>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-wallet"></i></div>
                        <div class="stat-info">
                            <p class="stat-label">Total Revenue</p>
                            <h3 class="stat-value">LKR <?php echo number_format($totalRevenue, 0); ?></h3>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <p class="stat-label">Total Customers</p>
                            <h3 class="stat-value"><?php echo number_format($totalCustomers); ?></h3>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fas fa-box"></i></div>
                        <div class="stat-info">
                            <p class="stat-label">Total Products</p>
                            <h3 class="stat-value"><?php echo number_format($totalProducts); ?></h3>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-chart-line"></i></div>
                        <div class="stat-info">
                            <p class="stat-label">Net Profit (Sales)</p>
                            <h3 class="stat-value">LKR <?php echo number_format($totalNetProfit, 0); ?></h3>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon teal"><i class="fas fa-tags"></i></div>
                        <div class="stat-info">
                            <p class="stat-label">Total Categories</p>
                            <h3 class="stat-value"><?php echo number_format($totalCategories); ?></h3>
                        </div>
                    </div>
                </div>

                <?php if (ezymart_sms_is_configured()): ?>
                <div style="margin-bottom:24px; padding:18px 22px; background:#001f52; border-radius:14px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                    <div style="color:#fff;">
                        <strong style="font-size:14px;"><i class="fas fa-sms"></i> SMS Campaign</strong>
                        <p style="margin:4px 0 0; font-size:12px; opacity:0.9;">Send promotional SMS to customers via Notify.lk</p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>admin/sms-campaign.php" style="padding:10px 18px; background:#fff; color:#003b94; border-radius:10px; font-size:13px; font-weight:600; text-decoration:none;">Open Campaign</a>
                </div>
                <?php endif; ?>

                <!-- Charts & Top Products Row -->
                <div class="dashboard-row three-col">
                    <!-- Revenue Overview -->
                    <div class="dashboard-card chart-card">
                        <div class="card-header">
                            <h3>Revenue Overview</h3>
                            <span class="card-filter" style="font-size:11px; color:#888;">Last 6 Weeks</span>
                        </div>
                        <div class="chart-placeholder">
                            <div class="chart-bars">
                                <?php foreach ($weeklyRevenue as $i => $week): 
                                    $height = $maxWeekRevenue > 0 ? round(($week['total'] / $maxWeekRevenue) * 100) : 0;
                                    if ($height < 5 && $week['total'] > 0) $height = 5;
                                ?>
                                <div class="chart-bar <?php echo $i === 3 ? 'highlight' : ''; ?>" style="height: <?php echo $height; ?>%;">
                                    <span><?php echo $week['label']; ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!empty($weeklyRevenue)): 
                                $maxWeek = $weeklyRevenue[0];
                                foreach ($weeklyRevenue as $w) { if ($w['total'] > $maxWeek['total']) $maxWeek = $w; }
                            ?>
                            <div class="chart-tooltip"><?php echo $maxWeek['label']; ?><br><strong>LKR <?php echo number_format($maxWeek['total'], 0); ?></strong></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Orders Overview -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Orders Overview</h3>
                        </div>
                        <div class="orders-overview">
                            <div class="donut-chart">
                                <div class="donut-center">
                                    <h4><?php echo number_format($totalOrders); ?></h4>
                                    <p>Total Orders</p>
                                </div>
                            </div>
                            <div class="order-legend">
                                <div class="legend-item">
                                    <span class="legend-dot green"></span>
                                    <span>Delivered</span>
                                    <strong><?php echo $completedPct; ?>% (<?php echo $completedOrders; ?>)</strong>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-dot blue"></span>
                                    <span>Processing</span>
                                    <strong><?php echo $processingPct; ?>% (<?php echo $processingOrders; ?>)</strong>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-dot orange"></span>
                                    <span>Pending</span>
                                    <strong><?php echo $pendingPct; ?>% (<?php echo $pendingOrders; ?>)</strong>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-dot red"></span>
                                    <span>Cancelled</span>
                                    <strong><?php echo $cancelledPct; ?>% (<?php echo $cancelledOrders; ?>)</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Selling Products -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Top Selling Products</h3>
                            <a href="<?php echo BASE_URL; ?>admin/products.php" class="view-all">View All</a>
                        </div>
                        <div class="top-products-header" style="display:flex; gap:12px; padding:0 8px 6px; font-size:10px; font-weight:600; color:#888; text-transform:uppercase;">
                            <span style="width:26px;"></span>
                            <span style="flex:1;">Product</span>
                            <span style="width:36px; text-align:center;">Sold</span>
                            <span style="width:72px; text-align:right;">Revenue</span>
                            <span style="width:72px; text-align:right;">Profit</span>
                        </div>
                        <div class="top-products">
                            <?php if (empty($topProducts)): ?>
                            <p style="text-align:center; color:#888; font-size:12px; padding:20px;">No sales data yet.</p>
                            <?php else: ?>
                            <?php foreach ($topProducts as $rank => $tp): ?>
                            <div class="product-row">
                                <span class="product-rank"><?php echo $rank + 1; ?></span>
                                <div class="product-info">
                                    <p><?php echo htmlspecialchars($tp['name']); ?></p>
                                </div>
                                <span class="product-sales"><?php echo number_format($tp['total_sold']); ?></span>
                                <span class="product-revenue">LKR <?php echo number_format($tp['revenue'], 0); ?></span>
                                <span class="product-revenue" style="color:<?php echo ($tp['net_profit'] ?? 0) >= 0 ? '#16a34a' : '#dc2626'; ?>;">LKR <?php echo number_format($tp['net_profit'] ?? 0, 0); ?></span>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Product Profit Margins -->
                <div class="dashboard-card" style="margin-bottom:24px;">
                    <div class="card-header">
                        <h3>Product Net Profit</h3>
                        <a href="<?php echo BASE_URL; ?>admin/products.php" class="view-all">View All Products</a>
                    </div>
                    <p style="font-size:12px; color:#888; margin:0 0 14px; padding:0 20px;">Per-unit profit = selling price − cost price. Selling price uses the current sale price (with discount if set).</p>
                    <div class="recent-orders" style="padding:0 20px 20px;">
                        <?php if (empty($productProfits)): ?>
                        <p style="text-align:center; color:#888; font-size:12px; padding:20px;">Add cost prices to your products to see profit margins here.</p>
                        <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Sell Price</th>
                                    <th>Cost</th>
                                    <th>Net Profit</th>
                                    <th>Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($productProfits, 0, 10) as $pp): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pp['name']); ?></td>
                                    <td>LKR <?php echo number_format($pp['sell_price'], 2); ?></td>
                                    <td>LKR <?php echo number_format($pp['cost_price'], 2); ?></td>
                                    <td style="color:<?php echo $pp['net_profit'] >= 0 ? '#16a34a' : '#dc2626'; ?>; font-weight:600;">
                                        LKR <?php echo number_format($pp['net_profit'], 2); ?>
                                    </td>
                                    <td><?php echo $pp['margin_pct']; ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bottom Row -->
                <div class="dashboard-row three-col">
                    <!-- Recent Orders -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Recent Orders</h3>
                            <a href="<?php echo BASE_URL; ?>admin/orders.php" class="view-all">View All</a>
                        </div>
                        <div class="recent-orders">
                            <?php if (empty($recentOrders)): ?>
                            <p style="text-align:center; color:#888; font-size:12px; padding:20px;">No orders yet.</p>
                            <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $ro): ?>
                                    <tr>
                                        <td class="order-id">#<?php echo htmlspecialchars($ro['order_number']); ?></td>
                                        <td><?php echo htmlspecialchars($ro['full_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($ro['created_at'])); ?></td>
                                        <td>LKR <?php echo number_format($ro['total'], 0); ?></td>
                                        <td><span class="status <?php echo $ro['order_status']; ?>"><?php echo ucfirst($ro['order_status']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Low Stock Alert</h3>
                            <a href="<?php echo BASE_URL; ?>admin/products.php" class="view-all">View All</a>
                        </div>
                        <div class="stock-alerts">
                            <?php if (empty($lowStockProducts)): ?>
                            <p style="text-align:center; color:#888; font-size:12px; padding:20px;">All products are well stocked!</p>
                            <?php else: ?>
                            <?php foreach ($lowStockProducts as $lsp): ?>
                            <div class="stock-item">
                                <div class="stock-info">
                                    <i class="fas fa-tshirt"></i>
                                    <span><?php echo htmlspecialchars($lsp['name']); ?></span>
                                </div>
                                <span class="stock-badge <?php echo $lsp['stock'] <= 5 ? 'critical' : 'warning'; ?>">Stock: <?php echo $lsp['stock']; ?></span>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Customers Overview -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Customers Overview</h3>
                            <a href="<?php echo BASE_URL; ?>admin/customers.php" class="view-all">View All</a>
                        </div>
                        <div class="customers-overview">
                            <div class="customer-stat">
                                <div class="customer-icon blue"><i class="fas fa-users"></i></div>
                                <div class="customer-info">
                                    <span>Total Customers</span>
                                    <strong><?php echo number_format($totalCustomers); ?></strong>
                                </div>
                            </div>
                            <div class="customer-stat">
                                <div class="customer-icon green"><i class="fas fa-user-plus"></i></div>
                                <div class="customer-info">
                                    <span>New (Last 30 Days)</span>
                                    <strong><?php echo number_format($newCustomers30d); ?></strong>
                                </div>
                            </div>
                            <div class="customer-stat">
                                <div class="customer-icon orange"><i class="fas fa-shopping-bag"></i></div>
                                <div class="customer-info">
                                    <span>Total Orders</span>
                                    <strong><?php echo number_format($totalOrders); ?></strong>
                                </div>
                            </div>
                            <div class="customer-stat">
                                <div class="customer-icon purple"><i class="fas fa-box"></i></div>
                                <div class="customer-info">
                                    <span>Pending Orders</span>
                                    <strong><?php echo number_format($pendingOrders); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="actions-grid">
                        <a href="<?php echo BASE_URL; ?>admin/add-product.php" class="action-btn"><i class="fas fa-plus-circle"></i> Add New Product</a>
                        <a href="<?php echo BASE_URL; ?>admin/categories.php" class="action-btn"><i class="fas fa-folder-plus"></i> Add New Category</a>
                        <a href="<?php echo BASE_URL; ?>admin/orders.php" class="action-btn"><i class="fas fa-shopping-bag"></i> Manage Orders</a>
                        <a href="<?php echo BASE_URL; ?>admin/customers.php" class="action-btn"><i class="fas fa-users-cog"></i> Manage Customers</a>
                        <a href="<?php echo BASE_URL; ?>admin/coupons.php" class="action-btn"><i class="fas fa-ticket-alt"></i> Create Coupon</a>
                        <a href="<?php echo BASE_URL; ?>admin/banners.php" class="action-btn"><i class="fas fa-image"></i> Upload Banner</a>
                        <a href="<?php echo BASE_URL; ?>admin/site-settings.php" class="action-btn"><i class="fas fa-cog"></i> Site Settings</a>
                    </div>
                </div>

                <!-- Footer -->
                <div class="admin-footer">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo ADMIN_BRAND; ?>. All rights reserved.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Sidebar Toggle
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.querySelector('.admin-layout').classList.toggle('sidebar-collapsed');
    });
    </script>
</body>
</html>
