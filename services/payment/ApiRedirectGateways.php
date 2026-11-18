<?php

require_once __DIR__ . '/PaymentGatewayInterface.php';
require_once __DIR__ . '/../../helpers/payment.php';

abstract class AbstractApiRedirectGateway implements PaymentGatewayInterface
{
    protected array $cfg;
    protected string $gatewayId;
    protected string $gatewayLabel;

    public function getId(): string
    {
        return $this->gatewayId;
    }

    public function getLabel(): string
    {
        return $this->gatewayLabel;
    }

    protected function apiUrl(string $path): string
    {
        return rtrim($this->cfg['api_base_url'], '/') . '/' . ltrim($path, '/');
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    protected function postJson(string $url, array $payload, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => $error ?: 'Request failed', 'http_code' => $httpCode];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return ['success' => false, 'error' => 'Invalid JSON response', 'raw' => $response, 'http_code' => $httpCode];
        }

        $decoded['http_code'] = $httpCode;
        return $decoded;
    }

    protected function extractRedirectUrl(array $response): ?string
    {
        foreach (['redirectUrl', 'redirect_url', 'paymentUrl', 'payment_url', 'checkoutUrl', 'checkout_url', 'url'] as $key) {
            if (!empty($response[$key]) && is_string($response[$key])) {
                return $response[$key];
            }
            if (!empty($response['data'][$key]) && is_string($response['data'][$key])) {
                return $response['data'][$key];
            }
        }

        return null;
    }
}

class PayzyGateway extends AbstractApiRedirectGateway
{
    public function __construct(?array $cfg = null)
    {
        $this->cfg = $cfg ?? ezymart_payment_gateway_config('payzy');
        $this->gatewayId = 'payzy';
        $this->gatewayLabel = 'Payzy';
    }

    public function isConfigured(): bool
    {
        return ezymart_payment_gateway_is_configured('payzy');
    }

    public function buildCheckoutContext(array $context): array
    {
        $amount = (float)($context['amount'] ?? 0);
        $orderNumber = (string)($context['order_number'] ?? '');
        $paymentInfo = $context['payment_info'] ?? [];

        $payload = [
            'merchantId' => $this->cfg['merchant_id'],
            'orderId' => $orderNumber,
            'amount' => (float)$amount,
            'currency' => 'LKR',
            'customer' => [
                'firstName' => $paymentInfo['fname'] ?? '',
                'lastName' => $paymentInfo['lname'] ?? '',
                'email' => $paymentInfo['email'] ?? '',
                'phone' => $paymentInfo['phone'] ?? '',
            ],
            'returnUrl' => ezymart_payment_callback_url('payzy', 'return'),
            'cancelUrl' => ezymart_payment_callback_url('payzy', 'cancel'),
            'notifyUrl' => ezymart_payment_notify_url('payzy'),
            'sandbox' => !empty($this->cfg['sandbox']),
        ];

        $headers = [];
        if (!empty($this->cfg['api_key'])) {
            $headers[] = 'Authorization: Bearer ' . $this->cfg['api_key'];
        }
        if (!empty($this->cfg['api_secret'])) {
            $headers[] = 'X-Api-Secret: ' . $this->cfg['api_secret'];
        }

        $response = $this->postJson($this->apiUrl($this->cfg['checkout_path']), $payload, $headers);
        $redirectUrl = $this->extractRedirectUrl($response);

        if ($redirectUrl) {
            return [
                'mode' => 'redirect',
                'redirect_url' => $redirectUrl,
            ];
        }

        return [
            'mode' => 'error',
            'error' => $response['message'] ?? $response['error'] ?? 'Unable to start Payzy checkout. Check API settings in Admin.',
            'debug' => $response,
        ];
    }

    public function verifyNotify(array $request): bool
    {
        return $this->verifyReturn($request);
    }

    public function verifyReturn(array $request): bool
    {
        $status = strtoupper((string)($request['status'] ?? $request['paymentStatus'] ?? $request['payment_status'] ?? ''));
        return in_array($status, ['SUCCESS', 'COMPLETED', 'APPROVED', 'PAID', '2'], true);
    }

    public function queryStatus(string $orderNumber): string
    {
        return 'UNKNOWN';
    }
}

class PayKokoGateway extends AbstractApiRedirectGateway
{
    public function __construct(?array $cfg = null)
    {
        $this->cfg = $cfg ?? ezymart_payment_gateway_config('paykoko');
        $this->gatewayId = 'paykoko';
        $this->gatewayLabel = 'PayKoko';
    }

    public function isConfigured(): bool
    {
        return ezymart_payment_gateway_is_configured('paykoko');
    }

    public function buildCheckoutContext(array $context): array
    {
        $amount = (float)($context['amount'] ?? 0);
        $orderNumber = (string)($context['order_number'] ?? '');
        $paymentInfo = $context['payment_info'] ?? [];

        $payload = [
            'merchantId' => $this->cfg['merchant_id'],
            'orderId' => $orderNumber,
            'amount' => (float)$amount,
            'currency' => 'LKR',
            'firstName' => $paymentInfo['fname'] ?? '',
            'lastName' => $paymentInfo['lname'] ?? '',
            'email' => $paymentInfo['email'] ?? '',
            'mobile' => $paymentInfo['phone'] ?? '',
            'returnUrl' => ezymart_payment_callback_url('paykoko', 'return'),
            'responseUrl' => ezymart_payment_notify_url('paykoko'),
            'cancelUrl' => ezymart_payment_callback_url('paykoko', 'cancel'),
        ];

        $headers = [];
        if (!empty($this->cfg['api_key'])) {
            $headers[] = 'X-Api-Key: ' . $this->cfg['api_key'];
        }

        $response = $this->postJson($this->apiUrl($this->cfg['checkout_path']), $payload, $headers);
        $redirectUrl = $this->extractRedirectUrl($response);

        if ($redirectUrl) {
            return [
                'mode' => 'redirect',
                'redirect_url' => $redirectUrl,
            ];
        }

        return [
            'mode' => 'error',
            'error' => $response['message'] ?? $response['error'] ?? 'Unable to start PayKoko checkout. Check API settings in Admin.',
            'debug' => $response,
        ];
    }

    public function verifyNotify(array $request): bool
    {
        if (!empty($this->cfg['public_key']) && !empty($request['signature'])) {
            $data = (string)($request['data'] ?? $request['payload'] ?? '');
            $signature = base64_decode((string)$request['signature'], true);
            if ($data !== '' && $signature !== false) {
                $key = openssl_pkey_get_public($this->cfg['public_key']);
                if ($key) {
                    $valid = openssl_verify($data, $signature, $key, OPENSSL_ALGO_SHA256);
                    if ($valid === 1) {
                        $decoded = json_decode($data, true);
                        $status = strtoupper((string)($decoded['status'] ?? $decoded['paymentStatus'] ?? ''));
                        return in_array($status, ['SUCCESS', 'COMPLETED', 'APPROVED', 'PAID'], true);
                    }
                }
            }
        }

        return $this->verifyReturn($request);
    }

    public function verifyReturn(array $request): bool
    {
        $status = strtoupper((string)($request['status'] ?? $request['paymentStatus'] ?? $request['orderStatus'] ?? ''));
        return in_array($status, ['SUCCESS', 'COMPLETED', 'APPROVED', 'PAID'], true);
    }

    public function queryStatus(string $orderNumber): string
    {
        return 'UNKNOWN';
    }
}
