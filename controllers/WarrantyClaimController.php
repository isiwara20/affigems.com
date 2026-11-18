<?php
/**
 * Warranty claim submission API.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/WarrantyClaimDAL.php';
require_once __DIR__ . '/../dal/WarrantyRegistryDAL.php';
require_once __DIR__ . '/../helpers/warranty_notifications.php';
require_once __DIR__ . '/../services/AsyncMailQueue.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$action = trim((string)($_POST['action'] ?? ''));
if ($action !== 'submit_warranty_claim') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

$registryId = (int)($_POST['registry_id'] ?? 0);
$issueType = trim((string)($_POST['issue_type'] ?? ''));
$issueDescription = trim((string)($_POST['issue_description'] ?? ''));
$invoiceNumber = trim((string)($_POST['invoice_number'] ?? ''));
$invoiceDate = trim((string)($_POST['invoice_date'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$customerName = trim((string)($_POST['customer_name'] ?? ''));

if ($registryId <= 0 || $issueType === '' || $issueDescription === '' || $phone === '' || $customerName === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if (mb_strlen($issueDescription) < 10) {
    echo json_encode(['success' => false, 'message' => 'Please describe the issue in a little more detail.']);
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

try {
    $registryDAL = new WarrantyRegistryDAL();
    $registry = $registryDAL->getById($registryId);
    if (!$registry) {
        echo json_encode(['success' => false, 'message' => 'Warranty record not found.']);
        exit;
    }

    if (!ezymart_warranty_is_active($registry['expires_at'])) {
        echo json_encode(['success' => false, 'message' => 'This product warranty has expired.']);
        exit;
    }

    $claimDAL = new WarrantyClaimDAL();
    if ($claimDAL->hasOpenClaimForRegistry($registryId)) {
        echo json_encode([
            'success' => false,
            'message' => 'A warranty claim is already active for this product. You can submit another claim only if the previous one is rejected.',
        ]);
        exit;
    }

    $attachmentPath = null;
    if (!empty($_FILES['attachment']) && ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['attachment'];
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Attachment upload failed.');
        }

        $maxSize = 10 * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'Attachment must be 10MB or smaller.']);
            exit;
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
        ];
        $mime = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
        if (!isset($allowed[$mime])) {
            echo json_encode(['success' => false, 'message' => 'Attachment must be a JPG, PNG, WEBP, MP4, or MOV file.']);
            exit;
        }

        $uploadDir = __DIR__ . '/../assets/uploads/warranty/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = 'claim_' . time() . '_' . uniqid() . '.' . $allowed[$mime];
        $destination = $uploadDir . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Could not save attachment.');
        }
        $attachmentPath = 'assets/uploads/warranty/' . $fileName;
    }

    // Always trust warranty registry record for order/invoice/product mapping.
    // This keeps order_id and invoice details aligned to the purchased product.
    $claimNumber = ezymart_warranty_generate_claim_number();
    $payload = [
        'claim_number' => $claimNumber,
        'registry_id' => $registryId,
        'order_id' => (int)($registry['order_id'] ?? 0) ?: null,
        'user_id' => !empty($_SESSION['logged_in']) ? (int)($_SESSION['user_id'] ?? 0) : ($registry['user_id'] ?? null),
        'invoice_number' => (string)$registry['invoice_number'],
        'invoice_date' => (string)$registry['purchase_date'],
        'serial_number' => $registry['serial_number'],
        'product_name' => $registry['product_name'],
        'customer_name' => $customerName,
        'email' => $email,
        'phone' => $phone,
        'issue_type' => $issueType,
        'issue_description' => $issueDescription,
        'attachment' => $attachmentPath,
        'status' => 'pending',
    ];

    if (!$claimDAL->create($payload)) {
        throw new RuntimeException('Could not save warranty claim.');
    }

    if ($attachmentPath) {
        $payload['attachment_url'] = absolute_asset_image_url($attachmentPath);
    }

    $payloadCopy = $payload;
    AsyncMailQueue::add(function () use ($payloadCopy) {
        ezymart_warranty_dispatch_notifications($payloadCopy);
    });

    AsyncMailQueue::sendResponse([
        'success' => true,
        'claim_number' => $claimNumber,
        'message' => 'Your warranty claim has been submitted successfully.',
    ]);

    AsyncMailQueue::flushAfterResponse([
        'handler' => 'warranty_claim_notify',
        'claim_number' => $claimNumber,
    ]);
    exit;
} catch (Throwable $e) {
    error_log('ARIGATO warranty claim error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'We could not submit your claim right now. Please try again or contact support.']);
    exit;
}
