<?php
/**
 * ARIGATO.LK — shared HTML email templates and send helper.
 */

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';
require_once __DIR__ . '/../libs/PHPMailer/Exception.php';

function lumak_email_brand_name(): string
{
    if (defined('ADMIN_BRAND')) {
        return ADMIN_BRAND;
    }
    return defined('SITE_NAME') ? SITE_NAME : 'Affinity Gems & Jewels';
}

function lumak_email_logo_url(): string
{
    if (!function_exists('absolute_asset_image_url')) {
        require_once __DIR__ . '/../config/app.php';
    }
    return absolute_asset_image_url('assets/images/logobottom.png');
}

function lumak_email_store_url(): string
{
    if (!function_exists('public_site_url')) {
        require_once __DIR__ . '/../config/app.php';
    }
    return public_site_url('');
}

function lumak_configure_phpmailer(\PHPMailer\PHPMailer\PHPMailer $mail): void
{
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->Port = MAIL_PORT;

    if (defined('MAIL_ENCRYPTION') && MAIL_ENCRYPTION === 'tls') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    } elseif (defined('MAIL_ENCRYPTION') && MAIL_ENCRYPTION === 'ssl') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = true;
    }

    if (MAIL_HOST === 'localhost' || MAIL_HOST === '127.0.0.1') {
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
    }

    $mail->Timeout = 120;
    $mail->CharSet = 'UTF-8';
}

function lumak_mail_temp_dir(): string
{
    $dir = __DIR__ . '/../assets/uploads/tmp';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        return $dir;
    }
    return sys_get_temp_dir();
}

function lumak_send_html_mail(string $toEmail, ?string $toName, string $subject, string $htmlBody, string $altBody = ''): bool
{
    if (empty($toEmail)) {
        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        lumak_configure_phpmailer($mail);
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName ?: '');
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $altBody !== '' ? $altBody : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log('ARIGATO.LK mail error: ' . $e->getMessage());
        return false;
    }
}

function lumak_send_mail_with_attachment(
    string $toEmail,
    ?string $toName,
    string $subject,
    string $htmlBody,
    string $attachmentData,
    string $attachmentName,
    string $attachmentMime = 'application/pdf',
    string $altBody = '',
    ?string $attachmentPath = null,
    bool $deleteAfterSend = true
): bool {
    if (empty($toEmail)) {
        return false;
    }

    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $attachmentName) ?: 'invoice.pdf';
    if (!preg_match('/\.pdf$/i', $safeName)) {
        $safeName .= '.pdf';
    }

    $plainAlt = $altBody !== '' ? $altBody : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

    $fileCandidates = [];
    if ($attachmentPath && is_file($attachmentPath) && is_readable($attachmentPath)) {
        $fileCandidates[] = $attachmentPath;
    }

    if ($attachmentData !== '' && strncmp($attachmentData, '%PDF', 4) === 0) {
        $tempDirs = array_values(array_unique([lumak_mail_temp_dir(), sys_get_temp_dir()]));
        foreach ($tempDirs as $dir) {
            $tmpPdfPath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'lumak_inv_' . uniqid('', true) . '.pdf';
            if (file_put_contents($tmpPdfPath, $attachmentData) !== false) {
                $fileCandidates[] = $tmpPdfPath;
            }
        }
    } elseif (empty($fileCandidates)) {
        error_log('ARIGATO.LK mail attachment error: no valid PDF file or data for ' . $safeName);
        return false;
    }

    $strategies = [];
    foreach ($fileCandidates as $path) {
        $strategies[] = ['type' => 'file', 'path' => $path, 'cleanup' => $deleteAfterSend && $path !== $attachmentPath];
    }
    if ($attachmentData !== '' && strncmp($attachmentData, '%PDF', 4) === 0) {
        $strategies[] = ['type' => 'string', 'path' => null, 'cleanup' => false];
    }

    foreach ($strategies as $strategy) {
        $tmpPdfPath = null;
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            lumak_configure_phpmailer($mail);
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($toEmail, $toName ?: '');
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $plainAlt;

            if ($strategy['type'] === 'string') {
                $mail->addStringAttachment(
                    $attachmentData,
                    $safeName,
                    \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64,
                    $attachmentMime
                );
            } else {
                $tmpPdfPath = $strategy['path'];
                $mail->addAttachment(
                    $tmpPdfPath,
                    $safeName,
                    \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64,
                    $attachmentMime
                );
            }

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            $detail = $e->getMessage();
            if (!empty($mail->ErrorInfo)) {
                $detail .= ' | ' . $mail->ErrorInfo;
            }
            error_log('ARIGATO.LK mail attachment error (' . $strategy['type'] . '): ' . $detail);
        } finally {
            if ($strategy['cleanup'] && $tmpPdfPath !== null && is_file($tmpPdfPath)) {
                @unlink($tmpPdfPath);
            }
        }
    }

    return false;
}

function lumak_email_button(string $url, string $label): string
{
    return '<table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td align="center" style="padding:18px 0 6px;">'
        . '<a href="' . htmlspecialchars($url) . '" style="display:inline-block;padding:14px 32px;background:#002868;color:#ffffff;text-decoration:none;border-radius:999px;font-size:14px;font-weight:700;">'
        . htmlspecialchars($label) . '</a></td></tr></table>';
}

function lumak_email_info_box(array $rows): string
{
    $html = '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f8fafd;border:1px solid #dbe5f5;border-radius:12px;margin:18px 0;">';
    foreach ($rows as $row) {
        $label = htmlspecialchars($row[0] ?? '');
        $value = htmlspecialchars($row[1] ?? '');
        $color = $row[2] ?? '#002868';
        $html .= '<tr><td style="padding:10px 16px;color:#888;font-size:12px;border-bottom:1px solid #f0e8ff;">' . $label . '</td>'
            . '<td style="padding:10px 16px;color:' . htmlspecialchars($color) . ';font-size:13px;font-weight:700;text-align:right;border-bottom:1px solid #f0e8ff;">' . $value . '</td></tr>';
    }
    $html .= '</table>';
    return $html;
}

function lumak_email_data_table(array $headers, array $rows): string
{
    $html = '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border:1px solid #dbe5f5;border-radius:12px;overflow:hidden;margin:16px 0;">';
    $html .= '<tr>';
    foreach ($headers as $header) {
        $html .= '<th style="padding:11px 12px;background:#e8f0fe;font-size:11px;text-transform:uppercase;letter-spacing:0.04em;color:#002868;text-align:left;">' . htmlspecialchars($header) . '</th>';
    }
    $html .= '</tr>';
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td style="padding:10px 12px;border-top:1px solid #e8f0fe;font-size:13px;color:#333;">' . $cell . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table>';
    return $html;
}

function lumak_email_wrap(string $subtitle, string $contentHtml): string
{
    $brand = htmlspecialchars(lumak_email_brand_name());
    $logo = htmlspecialchars(lumak_email_logo_url());
    $storeUrl = htmlspecialchars(lumak_email_store_url());
    $subtitleHtml = htmlspecialchars($subtitle);
    $year = date('Y');
    $phone = defined('STORE_PHONE_1') ? htmlspecialchars(STORE_PHONE_1) : '';
    $email = defined('STORE_EMAIL') ? htmlspecialchars(STORE_EMAIL) : '';

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
        . '<body style="margin:0;padding:0;background:#f8fafd;font-family:Poppins,Arial,sans-serif;color:#333;">'
        . '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f8fafd;padding:36px 16px;">'
        . '<tr><td align="center">'
        . '<table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;width:100%;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 40px rgba(1,43,127,0.14);">'
        . '<tr><td style="background:#002868;padding:28px 24px;text-align:center;">'
        . '<a href="' . $storeUrl . '" style="text-decoration:none;"><img src="' . $logo . '" alt="' . $brand . '" style="max-width:150px;height:auto;display:block;margin:0 auto 12px;"></a>'
        . '<p style="margin:0;color:rgba(255,255,255,0.92);font-size:13px;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;">' . $subtitleHtml . '</p>'
        . '</td></tr>'
        . '<tr><td style="padding:34px 30px 28px;">' . $contentHtml . '</td></tr>'
        . '<tr><td style="background:#f8fafd;padding:22px 30px;text-align:center;border-top:1px solid #f0e8ff;">'
        . '<p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#002868;">' . $brand . '</p>'
        . '<p style="margin:0 0 4px;font-size:11px;color:#888;">Direct Source for Sri Lankan Sapphires &amp; Fine Jewelry</p>'
        . ($phone ? '<p style="margin:0 0 4px;font-size:11px;color:#888;">' . $phone . '</p>' : '')
        . ($email ? '<p style="margin:0 0 8px;font-size:11px;color:#888;"><a href="mailto:' . $email . '" style="color:#003b94;text-decoration:none;">' . $email . '</a></p>' : '')
        . '<p style="margin:8px 0 0;font-size:11px;color:#aaa;">&copy; ' . $year . ' ' . $brand . '. All rights reserved.</p>'
        . '</td></tr></table></td></tr></table></body></html>';
}

function lumak_email_reset_password_template(string $name, string $resetLink): string
{
    $content = '<h2 style="margin:0 0 12px;font-size:22px;color:#002868;">Reset Your Password</h2>'
        . '<p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#555;">Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>'
        . '<p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#555;">We received a request to reset your password. Click the button below to choose a new one.</p>'
        . lumak_email_button($resetLink, 'Reset Password')
        . '<p style="margin:16px 0 0;font-size:12px;line-height:1.6;color:#888;">This link expires in <strong>1 hour</strong>. If you did not request this, you can safely ignore this email.</p>'
        . '<p style="margin:12px 0 0;font-size:12px;line-height:1.6;color:#888;word-break:break-all;">Or copy this link:<br><a href="' . htmlspecialchars($resetLink) . '" style="color:#002868;">' . htmlspecialchars($resetLink) . '</a></p>';

    return lumak_email_wrap('Account Security', $content);
}

function lumak_email_order_status_template(
    string $name,
    string $orderNumber,
    string $status,
    ?string $trackingId = null,
    ?string $courierCompany = null
): string
{
    $statusColors = [
        'pending' => '#ea580c',
        'processing' => '#2563eb',
        'shipped' => '#002868',
        'delivered' => '#16a34a',
        'cancelled' => '#ef4444',
    ];
    $statusMessages = [
        'pending' => 'Your order is pending confirmation. We will process it shortly.',
        'processing' => 'Great news! Your order is now being prepared and packed.',
        'shipped' => 'Your order has been shipped and is on its way to you.',
        'delivered' => 'Your order has been delivered. We hope you love your purchase!',
        'cancelled' => 'Your order has been cancelled. Contact us if you need help.',
    ];

    $color = $statusColors[$status] ?? '#002868';
    $msg = $statusMessages[$status] ?? 'Your order status has been updated.';

    $infoRows = [
        ['Order Number', '#' . $orderNumber],
        ['Status', ucfirst($status), $color],
    ];

    if ($status === 'shipped' && $courierCompany) {
        $infoRows[] = ['Courier', $courierCompany];
    }
    if ($status === 'shipped' && $trackingId) {
        $infoRows[] = ['Tracking ID', $trackingId];
    }

    $content = '<div style="text-align:center;margin-bottom:18px;">'
        . '<span style="display:inline-block;padding:8px 18px;background:' . $color . ';color:#fff;border-radius:999px;font-size:12px;font-weight:700;text-transform:uppercase;">' . htmlspecialchars(ucfirst($status)) . '</span></div>'
        . '<h2 style="margin:0 0 12px;font-size:22px;color:#002868;text-align:center;">Order Update</h2>'
        . '<p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#555;">Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>'
        . '<p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#555;">' . htmlspecialchars($msg) . '</p>'
        . lumak_email_info_box($infoRows)
        . lumak_email_button((defined('BASE_URL') ? BASE_URL : '/') . 'my-orders.php', 'View My Orders');

    return lumak_email_wrap('Order Status', $content);
}

function lumak_email_order_status_plaintext(
    string $name,
    string $orderNumber,
    string $status,
    ?string $trackingId = null,
    ?string $courierCompany = null
): string {
    $text = "Hi {$name}, your order #{$orderNumber} has been updated to: " . ucfirst($status) . '.';
    if ($status === 'shipped' && $courierCompany) {
        $text .= ' Courier: ' . $courierCompany . '.';
    }
    if ($status === 'shipped' && $trackingId) {
        $text .= ' Tracking ID: ' . $trackingId . '.';
    }
    return $text;
}

function lumak_email_payment_status_template(string $name, string $orderNumber, string $status, bool $invoiceAttached = false): string
{
    $statusColors = [
        'paid' => '#16a34a',
        'failed' => '#ef4444',
        'pending' => '#ea580c',
    ];
    $statusMessages = [
        'paid' => 'Your payment has been confirmed. Thank you! We are processing your order.',
        'failed' => 'Unfortunately your payment could not be confirmed. Please contact us or try again.',
        'pending' => 'Your payment is still pending. We will notify you once it is confirmed.',
    ];

    $color = $statusColors[$status] ?? '#002868';
    $msg = $statusMessages[$status] ?? 'Your payment status has been updated.';
    $label = $status === 'paid' ? 'Paid' : ucfirst($status);

    $invoiceNote = '';
    if ($status === 'paid' && $invoiceAttached) {
        $invoiceNote = '<p style="margin:0 0 16px;font-size:13px;line-height:1.7;color:#555;">Your invoice with a <strong>PAID</strong> stamp is attached to this email as a PDF.</p>';
    }

    $content = '<div style="text-align:center;margin-bottom:18px;">'
        . '<span style="display:inline-block;padding:8px 18px;background:' . $color . ';color:#fff;border-radius:999px;font-size:12px;font-weight:700;text-transform:uppercase;">' . htmlspecialchars($label) . '</span></div>'
        . '<h2 style="margin:0 0 12px;font-size:22px;color:#002868;text-align:center;">Payment Update</h2>'
        . '<p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#555;">Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>'
        . '<p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#555;">' . htmlspecialchars($msg) . '</p>'
        . lumak_email_info_box([
            ['Order Number', '#' . $orderNumber],
            ['Payment Status', $label, $color],
        ])
        . $invoiceNote
        . lumak_email_button((defined('BASE_URL') ? BASE_URL : '/') . 'my-orders.php', 'View My Orders');

    return lumak_email_wrap('Payment Status', $content);
}

function lumak_send_payment_status_notification(int $orderId, string $newStatus): bool
{
    if (!in_array($newStatus, ['paid', 'failed'], true)) {
        return false;
    }

    require_once __DIR__ . '/../dal/OrderDAL.php';

    $orderDAL = new OrderDAL();
    $order = $orderDAL->getById($orderId);
    if (!$order || empty($order['email'])) {
        return false;
    }

    $brand = lumak_email_brand_name();
    $subject = 'Order #' . $order['order_number'] . ' - Payment ' . ucfirst($newStatus) . ' | ' . $brand;
    $altBody = 'Hi ' . ($order['full_name'] ?? '') . ', payment for order #' . $order['order_number'] . ' is now: ' . $newStatus;

    if ($newStatus === 'paid') {
        try {
            if (!function_exists('lumak_invoice_prepare_pdf_for_payment')) {
                require_once __DIR__ . '/invoice.php';
            }
            $attachment = lumak_invoice_prepare_pdf_for_payment($orderId);
            if ($attachment && (!empty($attachment['path']) || !empty($attachment['data']))) {
                $htmlBody = lumak_email_payment_status_template(
                    $order['full_name'] ?? '',
                    $order['order_number'],
                    $newStatus,
                    true
                );
                $sent = lumak_send_mail_with_attachment(
                    $order['email'],
                    $order['full_name'] ?? '',
                    $subject,
                    $htmlBody,
                    $attachment['data'] ?? '',
                    $attachment['filename'],
                    'application/pdf',
                    $altBody . ' Your invoice PDF is attached.',
                    $attachment['path'] ?? null,
                    false
                );
                if ($sent) {
                    return true;
                }
                error_log('Payment paid email: PDF ready but attachment send failed for order #' . $orderId
                    . (isset($attachment['path']) ? ' file=' . $attachment['path'] : ''));
            } else {
                error_log('Payment paid email: could not prepare invoice PDF for order #' . $orderId);
            }
        } catch (\Throwable $e) {
            error_log('Payment email with invoice failed for order #' . $orderId . ': ' . $e->getMessage());
        }
    }

    return lumak_send_html_mail(
        $order['email'],
        $order['full_name'] ?? '',
        $subject,
        lumak_email_payment_status_template($order['full_name'] ?? '', $order['order_number'], $newStatus),
        $altBody
    );
}

function lumak_notify_customer_payment_status(int $orderId, string $newStatus): void
{
    lumak_send_payment_status_notification($orderId, $newStatus);
}

function lumak_email_low_stock_template(array $products): string
{
    $rows = [];
    foreach ($products as $product) {
        $rows[] = [
            htmlspecialchars($product['name'] ?? 'Product'),
            '<span style="color:#ef4444;font-weight:700;">' . (int)($product['stock'] ?? 0) . ' left</span>',
        ];
    }

    $content = '<h2 style="margin:0 0 12px;font-size:22px;color:#ef4444;">Low Stock Alert</h2>'
        . '<p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#555;">The following products are running low and may need restocking soon:</p>'
        . lumak_email_data_table(['Product', 'Stock'], $rows)
        . lumak_email_button((defined('BASE_URL') ? BASE_URL : '/') . 'admin/products.php', 'Manage Products');

    return lumak_email_wrap('Inventory Alert', $content);
}

function lumak_email_new_order_template(
    string $orderNumber,
    string $customerName,
    string $phone,
    string $customerEmail,
    float $total,
    array $items,
    array $options = []
): string {
    $isWhatsapp = !empty($options['whatsapp']);
    $cardConfirmed = !empty($options['card_confirmed']);
    $subtitle = $isWhatsapp ? 'New WhatsApp Order' : ($cardConfirmed ? 'Payment Confirmed' : 'New Order');

    $rows = [];
    foreach ($items as $item) {
        $name = $item['name'] ?? $item['product_name'] ?? 'Item';
        $qty = (int)($item['quantity'] ?? 1);
        $price = (float)($item['price'] ?? $item['unit_price'] ?? 0);
        $rows[] = [
            htmlspecialchars($name),
            (string)$qty,
            'LKR ' . number_format($price * $qty, 0),
        ];
    }

    $badge = '';
    if ($cardConfirmed) {
        $badge = '<div style="background:#ecfdf5;border:1px solid #bbf7d0;border-radius:10px;padding:12px 14px;margin:0 0 16px;font-size:13px;color:#166534;">Card payment successfully confirmed.</div>';
    } elseif ($isWhatsapp) {
        $badge = '<div style="background:#ecfdf5;border:1px solid #bbf7d0;border-radius:10px;padding:12px 14px;margin:0 0 16px;font-size:13px;color:#166534;">Placed via WhatsApp checkout.</div>';
    }

    $content = '<h2 style="margin:0 0 12px;font-size:22px;color:#002868;">Order #' . htmlspecialchars($orderNumber) . '</h2>'
        . $badge
        . lumak_email_info_box([
            ['Customer', $customerName],
            ['Phone', $phone],
            ['Email', $customerEmail ?: 'N/A'],
            ['Total', 'LKR ' . number_format($total, 0), '#002868'],
        ])
        . '<h3 style="margin:18px 0 10px;font-size:14px;color:#002868;">Order Items</h3>'
        . lumak_email_data_table(['Product', 'Qty', 'Total'], $rows)
        . lumak_email_button((defined('BASE_URL') ? BASE_URL : '/') . 'admin/orders.php', 'View in Admin');

    return lumak_email_wrap($subtitle, $content);
}

function lumak_email_newsletter_welcome_template(): string
{
    $brand = htmlspecialchars(lumak_email_brand_name());
    $content = '<h2 style="margin:0 0 12px;font-size:22px;color:#002868;">Welcome to ' . $brand . '!</h2>'
        . '<p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#555;">Thank you for subscribing to our newsletter.</p>'
        . '<p style="margin:0 0 10px;font-size:14px;line-height:1.7;color:#555;">You will receive updates on:</p>'
        . '<ul style="margin:0 0 18px;padding-left:20px;color:#555;font-size:14px;line-height:1.9;">'
        . '<li>Exclusive Ceylon sapphire arrivals and fine gemstones</li>'
        . '<li>Special offers and bespoke jewelry showcases</li>'
        . '<li>China tour highlights and international partnerships</li>'
        . '<li>Direct mine source announcements</li></ul>'
        . lumak_email_button(lumak_email_store_url() . 'shop.php', 'Browse the Collection');

    return lumak_email_wrap('Newsletter', $content);
}

function lumak_email_sanitize_admin_html(string $html): string
{
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? '';
    $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? '';
    $html = preg_replace('/\s+on\w+\s*=\s*("([^"]*)"|\'([^\']*)\'|[^\s>]+)/i', '', $html) ?? '';
    $html = preg_replace('/javascript\s*:/i', '', $html) ?? '';

    return trim($html);
}

function lumak_email_body_contains_html(string $body): bool
{
    return (bool)preg_match('/<\s*\w+[^>]*>/i', $body);
}

function lumak_email_newsletter_body_html(string $body): string
{
    $body = trim($body);
    if ($body === '') {
        return '';
    }

    if (lumak_email_body_contains_html($body)) {
        return lumak_email_sanitize_admin_html($body);
    }

    return nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
}

function lumak_email_newsletter_body_plain(string $body): string
{
    $body = trim($body);
    if ($body === '') {
        return '';
    }

    if (lumak_email_body_contains_html($body)) {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $body) ?? $body;
        $text = preg_replace('/<\/p>/i', "\n\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }

    return $body;
}

function lumak_email_newsletter_broadcast_template(string $subject, string $body): string
{
    $content = '<h2 style="margin:0 0 14px;font-size:22px;color:#002868;">' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</h2>'
        . '<div style="font-size:14px;line-height:1.8;color:#555;">' . lumak_email_newsletter_body_html($body) . '</div>'
        . lumak_email_button(lumak_email_store_url() . 'shop.php', 'Shop Now');

    return lumak_email_wrap('Newsletter', $content);
}

function lumak_email_chat_notification_template(
    string $userName,
    string $userEmail,
    int $conversationId,
    ?string $clientMessage = null
): string
{
    $intro = $clientMessage
        ? 'A customer sent a new message in live chat. Please log in to the admin dashboard and reply from Chat Support.'
        : 'A customer is requesting human assistance in live chat. Please log in to the admin dashboard and reply from Chat Support.';

    $rows = [
        ['Customer', $userName],
        ['Email', $userEmail ?: 'N/A'],
        ['Conversation', '#' . $conversationId],
    ];

    if ($clientMessage) {
        $rows[] = ['Message', $clientMessage];
    }

    $content = '<h2 style="margin:0 0 12px;font-size:22px;color:#002868;">New Chat Message</h2>'
        . '<p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#555;">' . htmlspecialchars($intro) . '</p>'
        . lumak_email_info_box($rows)
        . lumak_email_button((defined('BASE_URL') ? BASE_URL : '/') . 'admin/chats.php?id=' . $conversationId, 'Open Chat in Admin');

    return lumak_email_wrap('Chat Support', $content);
}

function lumak_email_chat_reply_template(string $userName, string $replyMessage): string
{
    $content = '<h2 style="margin:0 0 12px;font-size:22px;color:#002868;">Support Reply</h2>'
        . '<p style="margin:0 0 12px;font-size:14px;line-height:1.7;color:#555;">Hi <strong>' . htmlspecialchars($userName) . '</strong>,</p>'
        . '<p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#555;">Our support team has replied to your chat:</p>'
        . '<div style="border-left:4px solid #002868;padding:14px 18px;background:#f8fafd;border-radius:0 12px 12px 0;margin:0 0 18px;">'
        . '<p style="margin:0;font-size:14px;line-height:1.7;color:#333;">' . nl2br(htmlspecialchars($replyMessage)) . '</p></div>'
        . lumak_email_button(lumak_email_store_url() . 'contact.php', 'Continue on Website');

    return lumak_email_wrap('Customer Support', $content);
}

function lumak_send_low_stock_alert(array $products): void
{
    if (empty($products)) {
        return;
    }

    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    $alertEmail = '';
    try {
        $stmt = $conn->prepare("SELECT value FROM site_settings WHERE setting_key = 'low_stock_email'");
        $stmt->execute();
        $row = $stmt->fetch();
        $alertEmail = $row['value'] ?? '';
    } catch (\Throwable $e) {
        return;
    }

    if (empty($alertEmail)) {
        return;
    }

    lumak_send_html_mail(
        $alertEmail,
        'Store Admin',
        'Low Stock Alert - ' . lumak_email_brand_name(),
        lumak_email_low_stock_template($products)
    );
}

function lumak_send_new_order_notification(
    string $orderNumber,
    string $customerName,
    string $phone,
    string $customerEmail,
    float $total,
    array $items,
    array $options = []
): void {
    require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
    $settingsDAL = new SiteSettingsDAL();
    $notifyEmail = $settingsDAL->get('order_notification_email', '');
    if (empty($notifyEmail)) {
        return;
    }

    $isWhatsapp = !empty($options['whatsapp']);
    $cardConfirmed = !empty($options['card_confirmed']);
    $subject = 'New Order #' . $orderNumber;
    if ($isWhatsapp) {
        $subject .= ' (WhatsApp)';
    } elseif ($cardConfirmed) {
        $subject .= ' (Card Payment Confirmed)';
    }
    $subject .= ' - ' . lumak_email_brand_name();

    lumak_send_html_mail(
        $notifyEmail,
        'Store Admin',
        $subject,
        lumak_email_new_order_template($orderNumber, $customerName, $phone, $customerEmail, $total, $items, $options),
        "New order #{$orderNumber} from {$customerName}. Total: LKR " . number_format($total, 0)
    );
}
