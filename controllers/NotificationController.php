<?php
/**
 * Notification Controller
 * Returns recent order activity as notifications for the admin dashboard
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/OrderDAL.php';

header('Content-Type: application/json');

// Admin only
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['notifications' => [], 'unread_count' => 0]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_recent':
        getRecentNotifications();
        break;
    case 'mark_read':
        $_SESSION['admin_notif_read_at'] = date('Y-m-d H:i:s');
        echo json_encode(['success' => true]);
        break;
    case 'clear_all':
        $_SESSION['admin_notif_cleared_at'] = date('Y-m-d H:i:s');
        echo json_encode(['success' => true]);
        break;
    default:
        echo json_encode(['notifications' => [], 'unread_count' => 0]);
}

function getRecentNotifications() {
    $orderDAL = new OrderDAL();
    $orders = $orderDAL->getAll();
    
    // Only show orders from last 7 days, max 15
    $cutoff = date('Y-m-d H:i:s', strtotime('-7 days'));
    $clearedAt = $_SESSION['admin_notif_cleared_at'] ?? '2000-01-01 00:00:00';
    $readAt = $_SESSION['admin_notif_read_at'] ?? '2000-01-01 00:00:00';
    
    $notifications = [];
    $unreadCount = 0;
    
    foreach ($orders as $order) {
        if ($order['created_at'] < $cutoff) continue;
        if ($order['created_at'] < $clearedAt) continue;
        if (count($notifications) >= 15) break;
        
        // Determine notification type and message
        $type = 'order';
        $title = 'New Order';
        $message = '';
        $icon = 'fa-shopping-bag';
        
        if ($order['payment_method'] === 'whatsapp') {
            $type = 'whatsapp';
            $title = 'WhatsApp Order';
            $message = $order['full_name'] . ' placed order #' . $order['order_number'] . ' (LKR ' . number_format($order['total'], 0) . ')';
        } elseif ($order['payment_method'] === 'card' && $order['payment_status'] === 'paid') {
            $type = 'payment';
            $title = 'Payment Received';
            $message = 'Card payment confirmed for #' . $order['order_number'] . ' by ' . $order['full_name'];
        } elseif ($order['order_status'] === 'cancelled') {
            $type = 'cancelled';
            $title = 'Order Cancelled';
            $message = 'Order #' . $order['order_number'] . ' by ' . $order['full_name'] . ' was cancelled';
        } else {
            $message = $order['full_name'] . ' placed order #' . $order['order_number'] . ' (LKR ' . number_format($order['total'], 0) . ')';
        }
        
        // Determine URL
        $url = BASE_URL . 'admin/order-detail.php?id=' . $order['id'];
        if ($order['payment_method'] === 'whatsapp') {
            $url .= '&from=whatsapp';
        }
        
        // Time ago
        $timeAgo = getTimeAgo($order['created_at']);
        
        // Check if unread
        if ($order['created_at'] > $readAt) {
            $unreadCount++;
        }
        
        $notifications[] = [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'time_ago' => $timeAgo,
            'url' => $url
        ];
    }
    
    echo json_encode([
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);
}

function getTimeAgo($datetime) {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);
    
    if ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->i > 0) {
        return $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
    } else {
        return 'Just now';
    }
}
