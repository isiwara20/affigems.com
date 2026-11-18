<?php
/**
 * Warranty claim email notifications.
 */

require_once __DIR__ . '/email.php';
require_once __DIR__ . '/warranty.php';

function ezymart_warranty_admin_email(): string
{
    try {
        require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
        $dal = new SiteSettingsDAL();
        $orderEmail = trim($dal->get('order_notification_email', ''));
        if ($orderEmail !== '') {
            return $orderEmail;
        }
    } catch (Throwable $e) {
    }

    return defined('STORE_EMAIL') ? STORE_EMAIL : 'info@arigato.lk';
}

function ezymart_warranty_email_admin_template(array $data): string
{
    $statusLabel = ezymart_warranty_claim_status_label($data['status'] ?? 'pending');
    $rows = [
        ['Claim Number', $data['claim_number']],
        ['Product', $data['product_name']],
        ['Serial Number', $data['serial_number']],
        ['Invoice', $data['invoice_number']],
        ['Customer', $data['customer_name']],
        ['Phone', $data['phone']],
        ['Status', $statusLabel],
    ];
    if (!empty($data['email'])) {
        $rows[] = ['Email', $data['email']];
    }
    if (!empty($data['invoice_date'])) {
        $rows[] = ['Invoice Date', ezymart_warranty_format_date($data['invoice_date'])];
    }
    $rows[] = ['Issue Type', $data['issue_type']];

    $isUpdate = strtolower((string)($data['status'] ?? 'pending')) !== 'pending';
    $content = '<h2 style="margin:0 0 12px;font-size:22px;color:#D4A017;">'
        . ($isUpdate ? 'Warranty Claim Updated' : 'New Warranty Claim')
        . '</h2>'
        . '<p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#555;">A customer submitted a warranty claim on '
        . htmlspecialchars(lumak_email_brand_name()) . '.</p>'
        . lumak_email_info_box($rows)
        . '<p style="margin:18px 0 8px;font-size:12px;font-weight:700;color:#D4A017;text-transform:uppercase;letter-spacing:0.04em;">Description</p>'
        . '<div style="padding:16px 18px;background:#FFF8E6;border:1px solid #F5E6B8;border-radius:12px;font-size:14px;line-height:1.75;color:#333;white-space:pre-wrap;">'
        . nl2br(htmlspecialchars($data['issue_description'])) . '</div>';

    if (!empty($data['attachment_url'])) {
        $content .= '<p style="margin:18px 0 0;font-size:13px;"><a href="' . htmlspecialchars($data['attachment_url']) . '" style="color:#E5A91A;font-weight:600;">View attachment</a></p>';
    }

    if (!empty($data['admin_notes'])) {
        $content .= '<p style="margin:18px 0 0;font-size:13px;"><strong>Admin notes:</strong> ' . nl2br(htmlspecialchars($data['admin_notes'])) . '</p>';
    }

    $content .= lumak_email_button(lumak_email_store_url() . 'admin/warranty-claims.php', 'Open Admin Claims');

    return lumak_email_wrap('Warranty Claim', $content);
}

function ezymart_warranty_email_customer_template(array $data): string
{
    $status = strtolower((string)($data['status'] ?? 'pending'));
    $statusLabel = ezymart_warranty_claim_status_label($data['status'] ?? 'pending');
    $isUpdate = $status !== 'pending';

    $content = '<h2 style="margin:0 0 12px;font-size:22px;color:#D4A017;text-align:center;">'
        . ($isUpdate ? 'Warranty Claim Update' : 'Warranty Claim Received')
        . '</h2>'
        . '<p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#555;text-align:center;">Hi <strong>'
        . htmlspecialchars($data['customer_name']) . '</strong>, we received your warranty claim for <strong>'
        . htmlspecialchars($data['product_name']) . '</strong>.</p>'
        . lumak_email_info_box([
            ['Claim Number', $data['claim_number']],
            ['Status', $statusLabel],
            ['Product', $data['product_name']],
            ['Serial Number', $data['serial_number']],
        ])
        . '<p style="margin:0;font-size:14px;line-height:1.7;color:#555;text-align:center;">'
        . ($isUpdate ? 'Your warranty claim has been updated. Status: ' . $statusLabel . '.' : 'You can bring the product to our store or send it via courier.')
        . ' Track your claim status from your account.</p>'
        . lumak_email_button(lumak_email_store_url() . 'my-warranty-claims.php', 'View My Claims');

    return lumak_email_wrap('Warranty Claim', $content);
}

function ezymart_warranty_notify_admin(array $data): bool
{
    $statusLabel = ezymart_warranty_claim_status_label($data['status'] ?? 'pending');
    $subject = '[' . $data['claim_number'] . '] Warranty claim — ' . $data['product_name'] . ' (' . $statusLabel . ')';
    $alt = "New warranty claim {$data['claim_number']}\n"
        . "Product: {$data['product_name']}\n"
        . "Customer: {$data['customer_name']}\n"
        . "Phone: {$data['phone']}\n\n"
        . $data['issue_description'];

    return lumak_send_html_mail(
        ezymart_warranty_admin_email(),
        lumak_email_brand_name() . ' Warranty',
        $subject,
        ezymart_warranty_email_admin_template($data),
        $alt
    );
}

function ezymart_warranty_notify_customer(array $data): bool
{
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $statusLabel = ezymart_warranty_claim_status_label($data['status'] ?? 'pending');
    $subject = 'Warranty claim status — ' . $data['claim_number'] . ' (' . $statusLabel . ')';
    $alt = 'Your warranty claim ' . $data['claim_number'] . ' status is now: ' . $statusLabel . '.';

    return lumak_send_html_mail(
        $data['email'],
        $data['customer_name'],
        $subject,
        ezymart_warranty_email_customer_template($data),
        $alt
    );
}

function ezymart_warranty_dispatch_notifications(array $data): void
{
    ezymart_warranty_notify_admin($data);
    ezymart_warranty_notify_customer($data);
}
