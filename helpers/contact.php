<?php
/**
 * Contact form — reference numbers, emails, and SMS confirmations.
 */

require_once __DIR__ . '/email.php';
require_once __DIR__ . '/sms.php';

function ezymart_contact_generate_reference(): string
{
    return 'CM-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

function ezymart_contact_admin_email(): string
{
    return defined('STORE_EMAIL') ? STORE_EMAIL : 'info@arigato.lk';
}

function ezymart_contact_email_admin_template(array $data): string
{
    $rows = [
        ['Reference', $data['reference_no']],
        ['Name', $data['full_name']],
        ['Topic', $data['topic']],
        ['Subject', $data['subject']],
    ];
    if (!empty($data['phone'])) {
        $rows[] = ['Phone', $data['phone']];
    }
    if (!empty($data['email'])) {
        $rows[] = ['Email', $data['email']];
    }

    $content = '<h2 style="margin:0 0 12px;font-size:22px;color:#D4A017;">New Customer Enquiry</h2>'
        . '<p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#555;">A new message was submitted through the contact form on '
        . htmlspecialchars(lumak_email_brand_name()) . '.</p>'
        . lumak_email_info_box($rows)
        . '<p style="margin:18px 0 8px;font-size:12px;font-weight:700;color:#D4A017;text-transform:uppercase;letter-spacing:0.04em;">Message</p>'
        . '<div style="padding:16px 18px;background:#FFF8E6;border:1px solid #F5E6B8;border-radius:12px;font-size:14px;line-height:1.75;color:#333;white-space:pre-wrap;">'
        . nl2br(htmlspecialchars($data['message'])) . '</div>'
        . '<p style="margin:18px 0 0;font-size:12px;color:#888;">Submitted on ' . htmlspecialchars(date('d M Y, h:i A')) . '</p>';

    return lumak_email_wrap('Contact Form', $content);
}

function ezymart_contact_email_customer_template(array $data): string
{
    $name = htmlspecialchars($data['full_name']);
    $ref = htmlspecialchars($data['reference_no']);

    $content = '<h2 style="margin:0 0 12px;font-size:22px;color:#D4A017;text-align:center;">We Received Your Message</h2>'
        . '<p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#555;text-align:center;">Hi <strong>' . $name . '</strong>, thank you for contacting '
        . htmlspecialchars(lumak_email_brand_name()) . '. Your enquiry has been logged successfully.</p>'
        . '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:20px 0;">'
        . '<tr><td align="center">'
        . '<div style="display:inline-block;padding:18px 28px;background:linear-gradient(135deg,#FFF8E6,#fff);border:2px dashed #b8d4ff;border-radius:14px;text-align:center;">'
        . '<p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.08em;">Your Reference Number</p>'
        . '<p style="margin:0;font-size:26px;font-weight:800;color:#D4A017;letter-spacing:0.06em;">' . $ref . '</p>'
        . '</div></td></tr></table>'
        . lumak_email_info_box([
            ['Topic', $data['topic']],
            ['Subject', $data['subject']],
            ['Expected Response', '1–3 working days'],
        ])
        . '<p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#555;text-align:center;">Our customer care team will review your message and get back to you within <strong>1–3 working days</strong>. Please quote your reference number if you follow up by phone or email.</p>'
        . lumak_email_button(lumak_email_store_url() . 'contact.php', 'Visit Contact Page');

    return lumak_email_wrap('Enquiry Received', $content);
}

function ezymart_contact_sms_customer_message(string $referenceNo): string
{
    $brand = defined('SITE_NAME') ? SITE_NAME : 'ARIGATO.LK';
    return ezymart_sms_truncate(
        'Thank you for contacting ' . $brand . '. Your reference is ' . $referenceNo . '. We will respond within 1-3 working days.',
        621
    );
}

function ezymart_contact_notify_admin(array $data): bool
{
    $subject = '[' . $data['reference_no'] . '] ' . $data['topic'] . ' — ' . $data['subject'];
    $alt = "New contact enquiry ({$data['reference_no']})\n"
        . "Name: {$data['full_name']}\n"
        . "Topic: {$data['topic']}\n"
        . "Subject: {$data['subject']}\n\n"
        . $data['message'];

    return lumak_send_html_mail(
        ezymart_contact_admin_email(),
        lumak_email_brand_name() . ' Support',
        $subject,
        ezymart_contact_email_admin_template($data),
        $alt
    );
}

function ezymart_contact_notify_customer(array $data): array
{
    $result = ['email_sent' => false, 'sms_sent' => false];

    if (!empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $result['email_sent'] = lumak_send_html_mail(
            $data['email'],
            $data['full_name'],
            'We received your enquiry — Ref. ' . $data['reference_no'],
            ezymart_contact_email_customer_template($data),
            'Thank you for contacting us. Your reference number is ' . $data['reference_no']
                . '. We will respond within 1-3 working days.'
        );
    }

    if (!empty($data['phone']) && ezymart_sms_is_configured()) {
        $normalized = ezymart_normalize_phone($data['phone']);
        if ($normalized) {
            $result['sms_sent'] = ezymart_sms_send(
                $normalized,
                ezymart_contact_sms_customer_message($data['reference_no']),
                'contact_enquiry'
            );
        }
    }

    return $result;
}

function ezymart_contact_dispatch_notifications(array $payload): void
{
    ezymart_contact_notify_admin($payload);
    ezymart_contact_notify_customer($payload);
}

function ezymart_contact_dispatch_by_reference(string $referenceNo): bool
{
    require_once __DIR__ . '/../dal/ContactMessageDAL.php';

    $referenceNo = trim($referenceNo);
    if ($referenceNo === '') {
        return false;
    }

    $dal = new ContactMessageDAL();
    $payload = $dal->getByReferenceNo($referenceNo);
    if (!$payload) {
        error_log('ARIGATO contact notify: message not found for ' . $referenceNo);
        return false;
    }

    ezymart_contact_dispatch_notifications($payload);
    return true;
}
