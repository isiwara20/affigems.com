<?php

require_once __DIR__ . '/PaymentGatewayInterface.php';
require_once __DIR__ . '/../../helpers/payment.php';

class DirectPayGateway implements PaymentGatewayInterface
{
    private array $cfg;

    public function __construct(?array $cfg = null)
    {
        $this->cfg = $cfg ?? ezymart_payment_gateway_config('directpay');
    }

    public function getId(): string
    {
        return 'directpay';
    }

    public function getLabel(): string
    {
        return 'DirectPay';
    }

    public function isConfigured(): bool
    {
        return ezymart_payment_gateway_is_configured('directpay');
    }

    public function buildCheckoutContext(array $context): array
    {
        $amount = (float)($context['amount'] ?? 0);
        $orderNumber = (string)($context['order_number'] ?? '');
        $paymentInfo = $context['payment_info'] ?? [];

        $phone = trim((string)($paymentInfo['phone'] ?? ''));
        if (function_exists('ezymart_normalize_phone')) {
            $normalized = ezymart_normalize_phone($phone);
            if ($normalized && str_starts_with($normalized, '94')) {
                $phone = '0' . substr($normalized, 2);
            }
        }

        $email = trim((string)($paymentInfo['email'] ?? ''));
        if ($email === '' && defined('STORE_EMAIL')) {
            $email = STORE_EMAIL;
        }

        $payload = [
            'merchant_id' => $this->cfg['merchant_id'],
            'amount' => ezymart_payment_amount_string($amount),
            'type' => 'ONE_TIME',
            'order_id' => $orderNumber,
            'currency' => 'LKR',
            // DirectPay expects a simple callback URL (legacy format).
            'response_url' => absolute_url('controllers/PaymentCallback.php'),
            'first_name' => trim((string)($paymentInfo['fname'] ?? 'Customer')) ?: 'Customer',
            'last_name' => trim((string)($paymentInfo['lname'] ?? '')),
            'email' => $email,
            'phone' => $phone,
            'logo' => absolute_asset_image_url('assets/images/logo.png'),
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $encodedPayload = base64_encode($jsonPayload);
        $signature = hash_hmac('sha256', $encodedPayload, $this->cfg['secret_key']);

        return [
            'mode' => 'inline_js',
            'encoded_payload' => $encodedPayload,
            'signature' => $signature,
            'stage' => $this->cfg['stage'] ?? 'PROD',
            'sdk_url' => 'https://cdn.directpay.lk/v3/directpayipg.min.js',
        ];
    }

    public function verifyNotify(array $request): bool
    {
        return $this->verifyReturn($request);
    }

    public function verifyReturn(array $request): bool
    {
        $status = strtoupper((string)($request['status'] ?? ''));
        return in_array($status, ['SUCCESS', 'COMPLETED', 'APPROVED', 'CAPTURED', 'PAID'], true);
    }

    public function queryStatus(string $orderNumber): string
    {
        $queryPayload = [
            'merchant_id' => $this->cfg['merchant_id'],
            'order_id' => $orderNumber,
        ];

        $jsonPayload = json_encode($queryPayload, JSON_UNESCAPED_SLASHES);
        $encodedPayload = base64_encode($jsonPayload);
        $signature = hash_hmac('sha256', $encodedPayload, $this->cfg['secret_key']);

        $stage = strtoupper($this->cfg['stage'] ?? 'PROD');
        $queryUrl = $stage === 'SANDBOX'
            ? 'https://test-gateway.directpay.lk/api/v3/query'
            : 'https://gateway.directpay.lk/api/v3/query';

        $ch = curl_init($queryUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'dataString' => $encodedPayload,
                'signature' => $signature,
            ]),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            return 'ERROR';
        }

        $decoded = json_decode($response, true);
        if (isset($decoded['data'][0]['status'])) {
            return (string)$decoded['data'][0]['status'];
        }
        if (isset($decoded['data']['status'])) {
            return (string)$decoded['data']['status'];
        }

        return 'UNKNOWN';
    }
}
