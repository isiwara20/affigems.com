<?php
/**
 * DirectPay Payment Gateway Configuration (fallback defaults).
 * Live credentials are managed in Admin → Payment Methods.
 */

if (!function_exists('ezymart_payment_bootstrap_directpay_constants')) {
    function ezymart_payment_bootstrap_directpay_constants(): void
    {
        $merchantId = 'PI11698';
        $secretKey = 'e323367ed65ed033115ff27555a418fd4f0e534067c49e0a10b9949a871427a0';
        $stage = 'PROD';

        if (file_exists(__DIR__ . '/app.php')) {
            require_once __DIR__ . '/app.php';
            require_once __DIR__ . '/../helpers/payment.php';
            $cfg = ezymart_payment_gateway_config('directpay');
            if (!empty($cfg['merchant_id'])) {
                $merchantId = $cfg['merchant_id'];
            }
            if (!empty($cfg['secret_key'])) {
                $secretKey = $cfg['secret_key'];
            }
            if (!empty($cfg['stage'])) {
                $stage = $cfg['stage'];
            }
        }

        if (!defined('DIRECTPAY_MERCHANT_ID')) {
            define('DIRECTPAY_MERCHANT_ID', $merchantId);
        }
        if (!defined('DIRECTPAY_SECRET_KEY')) {
            define('DIRECTPAY_SECRET_KEY', $secretKey);
        }
        if (!defined('DIRECTPAY_STAGE')) {
            define('DIRECTPAY_STAGE', $stage);
        }
        if (!defined('DIRECTPAY_RESPONSE_URL')) {
            define('DIRECTPAY_RESPONSE_URL', function_exists('ezymart_payment_callback_url')
                ? ezymart_payment_callback_url('directpay', 'return')
                : '');
        }
        if (!defined('DIRECTPAY_LOGO')) {
            define('DIRECTPAY_LOGO', function_exists('absolute_asset_image_url')
                ? absolute_asset_image_url('assets/images/logo.png')
                : '');
        }
    }
}

ezymart_payment_bootstrap_directpay_constants();
