<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/SubscriberDAL.php';
require_once __DIR__ . '/../services/AsyncMailQueue.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'subscribe':
        handleSubscribe();
        break;
    case 'send_newsletter':
        sendNewsletter();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function handleSubscribe() {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        echo json_encode(['error' => 'Please enter a valid email address.']);
        return;
    }
    
    try {
        $subscriberDAL = new SubscriberDAL();
        
        if ($subscriberDAL->exists($email)) {
            echo json_encode(['success' => true, 'message' => 'You are already subscribed!']);
            return;
        }
        
        $subscriberDAL->subscribe($email);
        
        // Queue confirmation email to send AFTER response
        AsyncMailQueue::add(function() use ($email) {
            sendConfirmationEmail($email);
        });
        
        // Send response immediately (no waiting for email)
        AsyncMailQueue::sendResponse(['success' => true, 'message' => 'Successfully subscribed! Check your email for confirmation.']);
        AsyncMailQueue::flush();
        exit;
    } catch (Exception $e) {
        echo json_encode(['error' => 'Something went wrong. Please try again.']);
    }
}

function sendConfirmationEmail($email) {
    lumak_send_html_mail(
        $email,
        null,
        'Welcome to ' . lumak_email_brand_name() . ' Newsletter!',
        lumak_email_newsletter_welcome_template(),
        'Welcome to ' . lumak_email_brand_name() . ' Newsletter! You will receive updates on new arrivals, offers, and more.'
    );
}

function sendNewsletter() {
    // Admin only
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }
    
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    
    if (empty($subject) || empty($body)) {
        echo json_encode(['error' => 'Subject and message are required.']);
        return;
    }
    
    try {
        $subscriberDAL = new SubscriberDAL();
        $subscribers = $subscriberDAL->getActive();
        $sent = 0;
        $failed = 0;
        
        foreach ($subscribers as $sub) {
            try {
                $mailSent = lumak_send_html_mail(
                    $sub['email'],
                    null,
                    $subject,
                    lumak_email_newsletter_broadcast_template($subject, $body),
                    lumak_email_newsletter_body_plain($body)
                );
                if ($mailSent) {
                    $sent++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
            }
        }
        
        echo json_encode(['success' => true, 'message' => "Newsletter sent to {$sent} subscribers." . ($failed > 0 ? " ({$failed} failed)" : '')]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to send newsletter.']);
    }
}
