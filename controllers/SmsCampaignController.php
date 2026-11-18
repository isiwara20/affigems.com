<?php
/**
 * Admin SMS campaign sender (Notify.lk).
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/SmsLogDAL.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';
$message = trim($_POST['message'] ?? '');
$campaignName = trim($_POST['campaign_name'] ?? 'campaign');
$audience = $_POST['audience'] ?? 'manual';
$manualNumbers = trim($_POST['manual_numbers'] ?? '');

if ($message === '') {
    echo json_encode(['success' => false, 'error' => 'Message is required.']);
    exit;
}

if (!ezymart_sms_is_configured()) {
    echo json_encode(['success' => false, 'error' => 'SMS is not configured. Add Notify.lk User ID, API key, and Sender ID in Site Settings.']);
    exit;
}

$phones = [];

try {
    $smsLog = new SmsLogDAL();

    if ($audience === 'order_customers') {
        $phones = $smsLog->getDistinctOrderPhones();
    } elseif ($audience === 'registered_users') {
        $phones = $smsLog->getDistinctUserPhones();
    } elseif ($audience === 'manual') {
        $phones = ezymart_sms_parse_recipients($manualNumbers);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid audience.']);
        exit;
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Could not load recipients.']);
    exit;
}

if (empty($phones)) {
    echo json_encode(['success' => false, 'error' => 'No valid phone numbers found for this audience.']);
    exit;
}

$result = ezymart_sms_send_bulk($phones, $message, $campaignName !== '' ? $campaignName : 'campaign');

echo json_encode([
    'success' => true,
    'sent' => $result['sent'],
    'failed' => $result['failed'],
    'total' => $result['total'],
    'message' => sprintf('Campaign complete: %d sent, %d failed (of %d).', $result['sent'], $result['failed'], $result['total']),
]);
