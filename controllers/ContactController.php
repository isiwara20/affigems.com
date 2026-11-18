<?php
/**
 * Contact form submission — email to store + customer reference via email/SMS.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/ContactMessageDAL.php';
require_once __DIR__ . '/../helpers/contact.php';
require_once __DIR__ . '/../services/AsyncMailQueue.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$action = trim((string)($_POST['action'] ?? ''));
if ($action !== 'submit_contact') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

$name = trim((string)($_POST['name'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$topic = trim((string)($_POST['topic'] ?? 'General Enquiry'));
$subject = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($name === '' || $subject === '' || $message === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill in your name, subject, and message.']);
    exit;
}

if ($email === '' && $phone === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter your email address or phone number so we can send your reference.']);
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if (mb_strlen($message) < 10) {
    echo json_encode(['success' => false, 'message' => 'Please provide a little more detail in your message (at least 10 characters).']);
    exit;
}

try {
    $referenceNo = ezymart_contact_generate_reference();
    $payload = [
        'reference_no' => $referenceNo,
        'full_name' => $name,
        'phone' => $phone,
        'email' => $email,
        'topic' => $topic !== '' ? $topic : 'General Enquiry',
        'subject' => $subject,
        'message' => $message,
    ];

    $dal = new ContactMessageDAL();
    if (!$dal->create($payload)) {
        throw new RuntimeException('Could not save contact message.');
    }

    $payloadCopy = $payload;
    AsyncMailQueue::add(function () use ($payloadCopy) {
        ezymart_contact_dispatch_notifications($payloadCopy);
    });

    AsyncMailQueue::sendResponse([
        'success' => true,
        'reference_no' => $referenceNo,
        'message' => 'Your message has been sent successfully.',
        'response_time' => '1–3 working days',
    ]);

    // FPM: runs queued task after response. Apache: detached CLI worker when possible.
    AsyncMailQueue::flushAfterResponse([
        'handler' => 'contact_notify',
        'reference_no' => $referenceNo,
    ]);
    exit;
} catch (Throwable $e) {
    error_log('EZYMART contact form error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'We could not send your message right now. Please try again or call us directly.']);
    exit;
}
