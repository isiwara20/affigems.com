<?php
/**
 * WhatsApp order message helpers.
 */

function ezymart_whatsapp_number(): string
{
    $raw = defined('STORE_WHATSAPP') ? STORE_WHATSAPP : '';
    try {
        require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
        $fromSettings = trim((new SiteSettingsDAL())->get('whatsapp_number', ''));
        if ($fromSettings !== '') {
            $raw = $fromSettings;
        }
    } catch (Throwable $e) {
        // use constant fallback
    }

    $digits = preg_replace('/\D+/', '', $raw);
    if ($digits === '') {
        return '';
    }
    if (str_starts_with($digits, '0')) {
        $digits = '94' . substr($digits, 1);
    }
    return $digits;
}

function ezymart_whatsapp_order_message(
    string $orderNumber,
    string $fullName,
    string $phone,
    string $email,
    string $address,
    array $cartItems,
    float $total
): string {
    $brand = defined('SITE_NAME') ? SITE_NAME : 'Affinity Gems & Jewels';
    $lines = [
        '*New Order from ' . $brand . '*',
        '',
        '*Order #:* ' . $orderNumber,
        '*Customer:* ' . $fullName,
        '*Phone:* ' . $phone,
        '*Email:* ' . ($email !== '' ? $email : 'N/A'),
        '*Address:* ' . ($address !== '' ? $address : 'N/A'),
        '',
        '*Order Items:*',
    ];

    foreach ($cartItems as $item) {
        $lineTotal = (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1);
        $name = $item['name'] ?? 'Product';
        $qty = (int)($item['quantity'] ?? 1);
        $lines[] = '- ' . $name . ' x' . $qty . ' - ' . format_price($lineTotal);
    }

    $lines[] = '';
    $lines[] = '*Total: ' . format_price($total) . '*';
    $lines[] = '';
    $lines[] = 'Please confirm this order. Thank you!';

    return implode("\n", $lines);
}

function ezymart_whatsapp_order_url(string $message): string
{
    $number = ezymart_whatsapp_number();
    if ($number === '') {
        return '';
    }
    return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
}

/** Opens WhatsApp app directly when installed (mobile / desktop app). */
function ezymart_whatsapp_app_url(string $message): string
{
    $number = ezymart_whatsapp_number();
    if ($number === '') {
        return '';
    }
    return 'whatsapp://send?phone=' . $number . '&text=' . rawurlencode($message);
}

/** Mobile-friendly link that deep-links to the app more reliably than wa.me in some browsers. */
function ezymart_whatsapp_api_url(string $message): string
{
    $number = ezymart_whatsapp_number();
    if ($number === '') {
        return '';
    }
    return 'https://api.whatsapp.com/send?phone=' . $number . '&text=' . rawurlencode($message);
}

function ezymart_whatsapp_open_urls(string $message): array
{
    return [
        'web' => ezymart_whatsapp_order_url($message),
        'app' => ezymart_whatsapp_app_url($message),
        'api' => ezymart_whatsapp_api_url($message),
    ];
}
