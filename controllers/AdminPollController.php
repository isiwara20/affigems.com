<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Admin only
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = new Database();
$conn = $db->getConnection();

if ($action === 'check_new_orders') {
    $lastCheck = $_GET['since'] ?? date('Y-m-d H:i:s', strtotime('-30 seconds'));
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE created_at > :since");
    $stmt->execute([':since' => $lastCheck]);
    $result = $stmt->fetch();
    
    // Also get latest order count and total stats
    $stmtTotal = $conn->prepare("SELECT COUNT(*) as total_orders, COALESCE(SUM(total), 0) as total_revenue FROM orders");
    $stmtTotal->execute();
    $stats = $stmtTotal->fetch();
    
    // Pending orders count
    $stmtPending = $conn->prepare("SELECT COUNT(*) as pending FROM orders WHERE order_status = 'pending'");
    $stmtPending->execute();
    $pending = $stmtPending->fetch();
    
    echo json_encode([
        'new_orders' => (int)$result['count'],
        'total_orders' => (int)$stats['total_orders'],
        'total_revenue' => (float)$stats['total_revenue'],
        'pending_orders' => (int)$pending['pending'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} elseif ($action === 'check_low_stock') {
    $threshold = 5;
    try {
        $stmt = $conn->prepare("SELECT value FROM site_settings WHERE setting_key = 'low_stock_threshold'");
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) $threshold = (int)$row['value'];
    } catch (Exception $e) {}
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE stock < :threshold AND stock > 0 AND status = 'active'");
    $stmt->execute([':threshold' => $threshold]);
    $result = $stmt->fetch();
    
    echo json_encode([
        'low_stock_count' => (int)$result['count'],
        'threshold' => $threshold
    ]);
    
} else {
    echo json_encode(['error' => 'Invalid action']);
}
