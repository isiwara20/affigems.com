<?php
/**
 * ARIGATO.LK — payment gateway settings (admin-configurable).
 */

require_once __DIR__ . '/../dal/SiteSettingsDAL.php';

function ezymart_payment_gateway_ids(): array
{
    return ['directpay', 'payhere', 'payzy', 'paykoko'];
}

function ezymart_payment_gateway_labels(): array
{
    return [
        'directpay' => 'DirectPay',
        'payhere' => 'PayHere',
        'payzy' => 'Payzy',
        'paykoko' => 'PayKoko',
    ];
}

function ezymart_payment_settings(bool $refresh = false): array
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

    $fileMerchant = defined('DIRECTPAY_MERCHANT_ID') ? DIRECTPAY_MERCHANT_ID : '';
    $fileSecret = defined('DIRECTPAY_SECRET_KEY') ? DIRECTPAY_SECRET_KEY : '';
    $fileStage = defined('DIRECTPAY_STAGE') ? DIRECTPAY_STAGE : 'PROD';

    $cache = [
        'cod_enabled' => ($all['payment_cod_enabled'] ?? '1') === '1',
        'card_enabled' => ($all['payment_card_enabled'] ?? '1') === '1',
        'active_gateway' => trim($all['payment_card_gateway'] ?? 'directpay'),
        'gateways' => [
            'directpay' => [
                'enabled' => ($all['payment_directpay_enabled'] ?? '1') === '1',
                'merchant_id' => trim($all['directpay_merchant_id'] ?? $fileMerchant),
                'secret_key' => trim($all['directpay_secret_key'] ?? $fileSecret),
                'stage' => strtoupper(trim($all['directpay_stage'] ?? $fileStage)) === 'SANDBOX' ? 'SANDBOX' : 'PROD',
                'surcharge_percent' => (float)($all['directpay_surcharge_percent'] ?? 0),
            ],
            'payhere' => [
                'enabled' => ($all['payment_payhere_enabled'] ?? '0') === '1',
                'merchant_id' => trim($all['payhere_merchant_id'] ?? ''),
                'merchant_secret' => trim($all['payhere_merchant_secret'] ?? ''),
                'sandbox' => ($all['payhere_sandbox'] ?? '1') === '1',
                'surcharge_percent' => (float)($all['payhere_surcharge_percent'] ?? 0),
            ],
            'payzy' => [
                'enabled' => ($all['payment_payzy_enabled'] ?? '0') === '1',
                'merchant_id' => trim($all['payzy_merchant_id'] ?? ''),
                'api_key' => trim($all['payzy_api_key'] ?? ''),
                'api_secret' => trim($all['payzy_api_secret'] ?? ''),
                'api_base_url' => trim($all['payzy_api_base_url'] ?? 'https://api.payzy.lk'),
                'checkout_path' => trim($all['payzy_checkout_path'] ?? '/api/v1/checkout/session'),
                'sandbox' => ($all['payzy_sandbox'] ?? '1') === '1',
                'surcharge_percent' => (float)($all['payzy_surcharge_percent'] ?? 0),
            ],
            'paykoko' => [
                'enabled' => ($all['payment_paykoko_enabled'] ?? '0') === '1',
                'merchant_id' => trim($all['paykoko_merchant_id'] ?? ''),
                'api_key' => trim($all['paykoko_api_key'] ?? ''),
                'public_key' => trim($all['paykoko_public_key'] ?? ''),
                'private_key' => trim($all['paykoko_private_key'] ?? ''),
                'api_base_url' => trim($all['paykoko_api_base_url'] ?? ''),
                'checkout_path' => trim($all['paykoko_checkout_path'] ?? '/merchants/orderInit'),
                'sandbox' => ($all['paykoko_sandbox'] ?? '1') === '1',
                'surcharge_percent' => (float)($all['paykoko_surcharge_percent'] ?? 13),
            ],
        ],
    ];

    if ($cache['gateways']['paykoko']['api_base_url'] === '') {
        $cache['gateways']['paykoko']['api_base_url'] = $cache['gateways']['paykoko']['sandbox']
            ? 'https://qaapi.paykoko.com'
            : 'https://prodapi.paykoko.com';
    }

    return $cache;
}

function ezymart_payment_gateway_config(string $gateway): array
{
    $settings = ezymart_payment_settings();
    return $settings['gateways'][$gateway] ?? [];
}

function ezymart_payment_gateway_is_configured(string $gateway): bool
{
    $cfg = ezymart_payment_gateway_config($gateway);
    if (empty($cfg['enabled'])) {
        return false;
    }

    switch ($gateway) {
        case 'directpay':
            return ($cfg['merchant_id'] ?? '') !== '' && ($cfg['secret_key'] ?? '') !== '';
        case 'payhere':
            return ($cfg['merchant_id'] ?? '') !== '' && ($cfg['merchant_secret'] ?? '') !== '';
        case 'payzy':
            return ($cfg['merchant_id'] ?? '') !== '' && ($cfg['api_key'] ?? '') !== '';
        case 'paykoko':
            return ($cfg['merchant_id'] ?? '') !== '' && ($cfg['api_key'] ?? '') !== '';
        default:
            return false;
    }
}

function ezymart_payment_available_gateways(): array
{
    $available = [];
    foreach (ezymart_payment_gateway_ids() as $id) {
        if (ezymart_payment_gateway_is_configured($id)) {
            $available[] = $id;
        }
    }
    return $available;
}

function ezymart_payment_resolve_gateway(?string $paymentMethod = null): ?string
{
    $settings = ezymart_payment_settings();
    $available = ezymart_payment_available_gateways();

    if ($paymentMethod === 'card' || $paymentMethod === null) {
        $preferred = $settings['active_gateway'] ?? 'directpay';
        if (in_array($preferred, $available, true)) {
            return $preferred;
        }
        return $available[0] ?? null;
    }

    if (str_starts_with($paymentMethod, 'gateway_')) {
        $gateway = substr($paymentMethod, 8);
        return in_array($gateway, $available, true) ? $gateway : null;
    }

    if (in_array($paymentMethod, ezymart_payment_gateway_ids(), true)) {
        return in_array($paymentMethod, $available, true) ? $paymentMethod : null;
    }

    return null;
}

function ezymart_payment_callback_url(string $gateway, string $action = 'return'): string
{
    if (!function_exists('absolute_url')) {
        require_once __DIR__ . '/../config/app.php';
    }

    return absolute_url('controllers/PaymentCallback.php?gateway=' . rawurlencode($gateway) . '&action=' . rawurlencode($action));
}

function ezymart_payment_notify_url(string $gateway): string
{
    return ezymart_payment_callback_url($gateway, 'notify');
}

function ezymart_payment_amount_string(float $amount): string
{
    return number_format($amount, 2, '.', '');
}

function ezymart_payment_gateway_surcharge_percent(string $gateway): float
{
    $cfg = ezymart_payment_gateway_config($gateway);
    $percent = (float)($cfg['surcharge_percent'] ?? 0);
    return max(0, $percent);
}

function ezymart_payment_calculate_surcharge(float $baseAmount, string $gateway): float
{
    $percent = ezymart_payment_gateway_surcharge_percent($gateway);
    if ($percent <= 0 || $baseAmount <= 0) {
        return 0.0;
    }

    return round($baseAmount * ($percent / 100), 2);
}

function ezymart_payment_total_with_surcharge(float $baseAmount, string $gateway): float
{
    return round($baseAmount + ezymart_payment_calculate_surcharge($baseAmount, $gateway), 2);
}

function ezymart_payment_gateway_descriptions(): array
{
    return [
        'directpay' => 'Pay securely with Visa or Mastercard',
        'payhere' => 'Secure card payments powered by PayHere',
        'payzy' => 'Split your bill into interest-free instalments',
        'paykoko' => 'Pay in 3 easy instalments with zero interest',
    ];
}

function ezymart_payment_card_icon_base(): string
{
    return 'https://cdn.jsdelivr.net/gh/aaronfagan/svg-credit-card-payment-icons@main/flat-rounded/';
}

/**
 * @return array<string, array{label:string,alt:string}>
 */
function ezymart_payment_card_brands(): array
{
    $base = ezymart_payment_card_icon_base();

    return [
        'visa' => ['label' => 'Visa', 'icon' => $base . 'visa.svg'],
        'mastercard' => ['label' => 'Mastercard', 'icon' => $base . 'mastercard.svg'],
        'amex' => ['label' => 'Amex', 'icon' => $base . 'american-express.svg'],
        'discover' => ['label' => 'Discover', 'icon' => $base . 'discover.svg'],
    ];
}

/**
 * @return array<string, array{logo:string,type:string,cards:string[],accent:string,badge:string}>
 */
function ezymart_payment_gateway_branding(): array
{
    if (!defined('BASE_URL')) {
        require_once __DIR__ . '/../config/app.php';
    }

    $logo = static function (string $file): string {
        return BASE_URL . 'assets/images/payment/' . $file;
    };

    return [
        'directpay' => [
            'logo' => $logo('directpay.png'),
            'logo_bg' => '#111827',
            'type' => 'card',
            'cards' => ['visa', 'mastercard'],
            'accent' => '#0B3D91',
            'badge' => 'Card Payment',
        ],
        'payhere' => [
            'logo' => $logo('payhere.png'),
            'logo_bg' => '#111827',
            'type' => 'card',
            'cards' => ['visa', 'mastercard', 'amex'],
            'accent' => '#008CD6',
            'badge' => 'Card Payment',
        ],
        'payzy' => [
            'logo' => $logo('payzy-word.png'),
            'logo_bg' => '#0f172a',
            'type' => 'bnpl',
            'cards' => ['visa', 'mastercard'],
            'accent' => '#5B2EFF',
            'badge' => 'Pay Later',
        ],
        'paykoko' => [
            'logo' => $logo('paykoko.png'),
            'logo_bg' => '#111827',
            'type' => 'bnpl',
            'cards' => ['visa', 'mastercard'],
            'accent' => '#F472B6',
            'badge' => 'Pay Later',
        ],
    ];
}

function ezymart_payment_is_online_method(string $paymentMethod): bool
{
    return $paymentMethod === 'card' || str_starts_with($paymentMethod, 'gateway_')
        || in_array($paymentMethod, ezymart_payment_gateway_ids(), true);
}
