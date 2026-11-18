<?php
/**
 * Mail Service
 * Handles sending emails via PHPMailer + Gmail SMTP
 */

require_once __DIR__ . '/../helpers/email.php';

class MailService {

  public function sendPasswordResetEmail($toEmail, $toName, $resetLink) {
    $brand = lumak_email_brand_name();
    $sent = lumak_send_html_mail(
      $toEmail,
      $toName,
      'Reset Your Password - ' . $brand,
      lumak_email_reset_password_template($toName, $resetLink),
      "Hi {$toName}, click this link to reset your password: {$resetLink} (expires in 1 hour.)"
    );

    if ($sent) {
      return ['success' => true, 'message' => 'Email sent successfully.'];
    }
    return ['success' => false, 'message' => 'Failed to send email.'];
  }

  public function sendOrderStatusEmail($toEmail, $customerName, $orderNumber, $newStatus, ?string $trackingId = null, ?string $courierCompany = null) {
    $brand = lumak_email_brand_name();
    $sent = lumak_send_html_mail(
      $toEmail,
      $customerName,
      'Order #' . $orderNumber . ' - ' . ucfirst($newStatus) . ' | ' . $brand,
      lumak_email_order_status_template($customerName, $orderNumber, $newStatus, $trackingId, $courierCompany),
      lumak_email_order_status_plaintext($customerName, $orderNumber, $newStatus, $trackingId, $courierCompany)
    );

    return $sent ? ['success' => true] : ['success' => false, 'message' => 'Failed to send email.'];
  }

  public function sendPaymentStatusEmail($toEmail, $customerName, $orderNumber, $newStatus) {
    $brand = lumak_email_brand_name();
    $sent = lumak_send_html_mail(
      $toEmail,
      $customerName,
      'Order #' . $orderNumber . ' - Payment ' . ucfirst($newStatus) . ' | ' . $brand,
      lumak_email_payment_status_template($customerName, $orderNumber, $newStatus),
      "Hi {$customerName}, payment for order #{$orderNumber} is now: " . ucfirst($newStatus)
    );

    return $sent ? ['success' => true] : ['success' => false, 'message' => 'Failed to send email.'];
  }
}
