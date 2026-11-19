<?php
/**
 * ARIGATO.LK — SMS notifications (Notify.lk + ShoutOUT)
 * Notify.lk API: https://developer.notify.lk/api-endpoints/
 * ShoutOUT API: https://api.getshoutout.com/coreservice
 */

require_once __DIR__ . '/../dal/SiteSettingsDAL.php';

function ezymart_sms_settings(bool $refresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$refresh) {
        return $cache;
    }

    try {
        $dal = new SiteSettingsDAL();
        $all = $dal->getAll();
    } catch (Throwable $e) {
        $all = [];
    }

    $provider = trim($all['sms_api_provider'] ?? 'notify_lk');
    if (!in_array($provider, ['notify_lk', 'shoutout'], true)) {
        $provider = 'notify_lk';
    }

    $cache = [
        'enabled' => ($all['sms_enabled'] ?? '0') === '1',
        'provider' => $provider,
        'user_id' => trim($all['sms_api_user_id'] ?? ''),
        'api_key' => trim($all['sms_api_key'] ?? ''),
        'sender_id' => trim($all['sms_sender_id'] ?? ''),
        'shoutout_api_key' => trim($all['sms_shoutout_api_key'] ?? ''),
        'shoutout_source' => trim($all['sms_shoutout_source'] ?? ''),
        'order_placed' => ($all['sms_order_placed_enabled'] ?? '1') === '1',
        'order_status' => ($all['sms_order_status_enabled'] ?? '1') === '1',
        'admin_new_order' => ($all['sms_admin_new_order_enabled'] ?? '1') === '1',
        'admin_low_stock' => ($all['sms_admin_low_stock_enabled'] ?? '1') === '1',
        'order_notification_sms' => trim($all['order_notification_sms'] ?? ''),
        'low_stock_sms' => trim($all['low_stock_sms'] ?? ''),
        'store_name' => trim($all['store_name'] ?? (defined('SITE_NAME') ? SITE_NAME : 'Affinity Gems & Jewels')),
    ];

    return $cache;
}

function ezymart_sms_provider_label(?string $provider = null): string
{
    $provider = $provider ?? (ezymart_sms_settings()['provider'] ?? 'notify_lk');
    return $provider === 'shoutout' ? 'ShoutOUT' : 'Notify.lk';
}

function ezymart_sms_is_configured(): bool
{
    $s = ezymart_sms_settings();
    if (!$s['enabled']) {
        return false;
    }

    if ($s['provider'] === 'shoutout') {
        return $s['shoutout_api_key'] !== '' && $s['shoutout_source'] !== '';
    }

    return $s['user_id'] !== '' && $s['api_key'] !== '' && $s['sender_id'] !== '';
}

function ezymart_normalize_phone(?string $phone): ?string
{
    if ($phone === null || trim($phone) === '') {
        return null;
    }

    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === '') {
        return null;
    }

    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }

    if (str_starts_with($digits, '0')) {
        $digits = '94' . substr($digits, 1);
    }

    if (!str_starts_with($digits, '94') && strlen($digits) === 9) {
        $digits = '94' . $digits;
    }

    if (strlen($digits) < 10 || strlen($digits) > 15) {
        return null;
    }

    return $digits;
}

/**
 * @return string[]
 */
function ezymart_sms_parse_recipients(string $raw): array
{
    $parts = preg_split('/[,;\n]+/', $raw) ?: [];
    $out = [];
    foreach ($parts as $part) {
        $normalized = ezymart_normalize_phone(trim($part));
        if ($normalized) {
            $out[$normalized] = $normalized;
        }
    }
    return array_values($out);
}

/**
 * Convert smart punctuation and non-GSM characters so SMS does not show "?" on phones.
 */
function ezymart_sms_sanitize_gsm(string $message): string
{
    $replacements = [
        "\u{2014}" => '-',  // em dash
        "\u{2013}" => '-',  // en dash
        "\u{2212}" => '-',  // minus
        "\u{2018}" => "'",
        "\u{2019}" => "'",
        "\u{201C}" => '"',
        "\u{201D}" => '"',
        "\u{2026}" => '...',
        "\u{00A0}" => ' ',  // nbsp
        '—' => '-',
        '–' => '-',
        '‘' => "'",
        '’' => "'",
        '“' => '"',
        '”' => '"',
        '…' => '...',
    ];

    $message = strtr($message, $replacements);
    $message = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $message) ?? '';

    return trim(preg_replace('/\s+/', ' ', $message) ?? '');
}

function ezymart_sms_truncate(string $message, int $max = 621): string
{
    $message = ezymart_sms_sanitize_gsm($message);
    if (strlen($message) <= $max) {
        return $message;
    }
    return rtrim(substr($message, 0, $max - 3)) . '...';
}

function ezymart_sms_send_notify_lk(string $userId, string $apiKey, string $senderId, string $destination, string $message): void
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL extension is not available for SMS');
    }

    $message = ezymart_sms_truncate($message, 621);

    $post = [
        'user_id' => $userId,
        'api_key' => $apiKey,
        'sender_id' => $senderId,
        'to' => $destination,
        'message' => $message,
    ];

    $ch = curl_init('https://app.notify.lk/api/v1/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        throw new RuntimeException('cURL error: ' . $curlError);
    }

    $data = json_decode((string)$response, true);
    if ($httpCode >= 200 && $httpCode < 300 && is_array($data) && ($data['status'] ?? '') === 'success') {
        return;
    }

    $error = is_array($data) ? ($data['message'] ?? $data['data'] ?? json_encode($data)) : (string)$response;
    throw new RuntimeException('Notify.lk HTTP ' . $httpCode . ': ' . $error);
}

function ezymart_sms_send_shoutout(string $apiKey, string $source, string $destination, string $message): void
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL extension is not available for SMS');
    }

    $message = ezymart_sms_truncate($message, 480);

    $payload = json_encode([
        'source' => $source,
        'destinations' => [$destination],
        'content' => ['sms' => $message],
        'transports' => ['SMS'],
    ], JSON_UNESCAPED_UNICODE);

    if ($payload === false) {
        throw new RuntimeException('Failed to encode ShoutOUT SMS payload');
    }

    $ch = curl_init('https://api.getshoutout.com/coreservice/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Apikey ' . $apiKey,
        ],
    ]);
    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        throw new RuntimeException('cURL error: ' . $curlError);
    }

    $data = json_decode((string) $response, true);
    if ($httpCode >= 200 && $httpCode < 300) {
        return;
    }

    $error = is_array($data)
        ? ($data['message'] ?? $data['error'] ?? json_encode($data))
        : (string) $response;
    throw new RuntimeException('ShoutOUT HTTP ' . $httpCode . ': ' . $error);
}

function ezymart_sms_send_http(array $settings, string $destination, string $message): void
{
    if (($settings['provider'] ?? 'notify_lk') === 'shoutout') {
        ezymart_sms_send_shoutout(
            $settings['shoutout_api_key'],
            $settings['shoutout_source'],
            $destination,
            $message
        );
        return;
    }

    ezymart_sms_send_notify_lk(
        $settings['user_id'],
        $settings['api_key'],
        $settings['sender_id'],
        $destination,
        $message
    );
}

function ezymart_sms_send(string $to, string $message, ?string $campaign = null): bool
{
    if (!ezymart_sms_is_configured()) {
        return false;
    }

    $destination = ezymart_normalize_phone($to);
    if (!$destination) {
        ezymart_sms_log($campaign, $to, $message, false, 'Invalid phone number');
        return false;
    }

    $settings = ezymart_sms_settings();
    $message = ezymart_sms_truncate($message);

    try {
        ezymart_sms_send_http($settings, $destination, $message);
        ezymart_sms_log($campaign, $destination, $message, true);
        return true;
    } catch (Throwable $e) {
        error_log('ARIGATO SMS error: ' . $e->getMessage());
        ezymart_sms_log($campaign, $destination, $message, false, $e->getMessage());
        return false;
    }
}

/**
 * @param string[] $phones
 * @return array{sent:int, failed:int, total:int}
 */
function ezymart_sms_send_bulk(array $phones, string $message, ?string $campaign = null): array
{
    $sent = 0;
    $failed = 0;
    $seen = [];

    foreach ($phones as $phone) {
        $normalized = ezymart_normalize_phone((string)$phone);
        if (!$normalized || isset($seen[$normalized])) {
            continue;
        }
        $seen[$normalized] = true;
        if (ezymart_sms_send($normalized, $message, $campaign)) {
            $sent++;
        } else {
            $failed++;
        }
        usleep(80000);
    }

    return ['sent' => $sent, 'failed' => $failed, 'total' => count($seen)];
}

function ezymart_sms_log(?string $campaign, string $recipient, string $message, bool $success, ?string $error = null): void
{
    try {
        require_once __DIR__ . '/../dal/SmsLogDAL.php';
        $log = new SmsLogDAL();
        $log->create([
            'campaign_name' => $campaign,
            'recipient' => $recipient,
            'message' => $message,
            'status' => $success ? 'sent' : 'failed',
            'error_message' => $error,
        ]);
    } catch (Throwable $e) {
        error_log('ARIGATO SMS log error: ' . $e->getMessage());
    }
}

function ezymart_sms_status_label(string $status): string
{
    $map = [
        'pending' => 'received and is pending',
        'processing' => 'being processed',
        'shipped' => 'on its way to you',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
    ];
    return $map[strtolower($status)] ?? $status;
}

function ezymart_sms_notify_order_placed(string $phone, string $orderNumber, float $total): void
{
    $settings = ezymart_sms_settings();
    if (!$settings['enabled'] || !$settings['order_placed']) {
        return;
    }

    $message = sprintf(
        'Thank you! Order #%s (LKR %s) was placed successfully. We will update you when it ships.',
        $orderNumber,
        number_format($total, 0)
    );
    ezymart_sms_send($phone, $message, 'order_placed');
}

function ezymart_sms_notify_order_status(
    string $phone,
    string $orderNumber,
    string $status,
    ?string $trackingId = null,
    ?string $courierCompany = null
): void
{
    $settings = ezymart_sms_settings();
    if (!$settings['enabled'] || !$settings['order_status']) {
        return;
    }

    $label = ezymart_sms_status_label($status);

    if ($status === 'shipped' && $trackingId && $courierCompany) {
        $message = sprintf(
            'Your order #%s has been shipped via %s. Tracking ID: %s.',
            $orderNumber,
            $courierCompany,
            $trackingId
        );
    } else {
        $message = sprintf(
            'Your order #%s is now %s.',
            $orderNumber,
            $label
        );
    }

    ezymart_sms_send($phone, $message, 'order_status');
}

function ezymart_sms_notify_admin_new_order(string $orderNumber, string $customerName, float $total, array $options = []): void
{
    $settings = ezymart_sms_settings();
    if (!$settings['enabled'] || !$settings['admin_new_order']) {
        return;
    }

    $phones = ezymart_sms_parse_recipients($settings['order_notification_sms']);
    if (empty($phones)) {
        return;
    }

    $suffix = '';
    if (!empty($options['whatsapp'])) {
        $suffix = ' (WhatsApp)';
    } elseif (!empty($options['card_confirmed'])) {
        $suffix = ' (Card paid)';
    }

    $message = sprintf(
        'New order%s #%s from %s. Total LKR %s. Check admin panel.',
        $suffix,
        $orderNumber,
        $customerName,
        number_format($total, 0)
    );
    ezymart_sms_send_bulk($phones, $message, 'admin_new_order');
}

function ezymart_sms_notify_invoice(string $phone, string $orderNumber, string $invoiceNo, string $invoiceUrl): bool
{
    $settings = ezymart_sms_settings();
    if (!$settings['enabled']) {
        return false;
    }

    $store = $settings['store_name'] !== '' ? $settings['store_name'] : (defined('SITE_NAME') ? SITE_NAME : 'Affinity Gems & Jewels');
    $message = sprintf(
        '%s: Invoice %s for your order #%s is ready. Download PDF: %s',
        $store,
        $invoiceNo,
        $orderNumber,
        $invoiceUrl
    );

    return ezymart_sms_send($phone, $message, 'invoice');
}

function ezymart_sms_notify_admin_low_stock(array $products): void
{
    if (empty($products)) {
        return;
    }

    $settings = ezymart_sms_settings();
    if (!$settings['enabled'] || !$settings['admin_low_stock']) {
        return;
    }

    $phones = ezymart_sms_parse_recipients($settings['low_stock_sms']);
    if (empty($phones)) {
        return;
    }

    $names = [];
    foreach (array_slice($products, 0, 3) as $product) {
        $names[] = ($product['name'] ?? 'Product') . ' (' . (int)($product['stock'] ?? 0) . ' left)';
    }
    $more = count($products) > 3 ? ' +' . (count($products) - 3) . ' more' : '';

    $message = sprintf(
        'Low stock alert: %s%s. Restock soon.',
        implode(', ', $names),
        $more
    );
    ezymart_sms_send_bulk($phones, $message, 'admin_low_stock');
}
