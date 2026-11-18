<?php

require_once __DIR__ . '/PaymentGatewayInterface.php';
require_once __DIR__ . '/../../helpers/payment.php';

class PayHereGateway implements PaymentGatewayInterface
{
    private array $cfg;

    public function __construct(?array $cfg = null)
    {
        $this->cfg = $cfg ?? ezymart_payment_gateway_config('payhere');
    }

    public function getId(): string
    {
        return 'payhere';
    }

    public function getLabel(): string
    {
        return 'PayHere';
    }

    public function isConfigured(): bool
    {
        return ezymart_payment_gateway_is_configured('payhere');
    }

    private function checkoutUrl(): string
    {
        return !empty($this->cfg['sandbox'])
            ? 'https://sandbox.payhere.lk/pay/checkout'
            : 'https://www.payhere.lk/pay/checkout';
    }

    private function generateHash(string $orderId, float $amount, string $currency = 'LKR'): string
    {
        $formatted = ezymart_payment_amount_string($amount);
        $secretHash = strtoupper(md5($this->cfg['merchant_secret']));

        return strtoupper(md5(
            $this->cfg['merchant_id'] .
            $orderId .
            $formatted .
            $currency .
            $secretHash
        ));
    }

    public function buildCheckoutContext(array $context): array
    {
        $amount = (float)($context['amount'] ?? 0);
        $orderNumber = (string)($context['order_number'] ?? '');
        $paymentInfo = $context['payment_info'] ?? [];
        $address = $context['address'] ?? [];

        $fields = [
            'merchant_id' => $this->cfg['merchant_id'],
            'return_url' => ezymart_payment_callback_url('payhere', 'return'),
            'cancel_url' => ezymart_payment_callback_url('payhere', 'cancel'),
            'notify_url' => ezymart_payment_notify_url('payhere'),
            'order_id' => $orderNumber,
            'items' => 'EZYMART Order ' . $orderNumber,
            'currency' => 'LKR',
            'amount' => ezymart_payment_amount_string($amount),
            'first_name' => $paymentInfo['fname'] ?? '',
            'last_name' => $paymentInfo['lname'] ?? '',
            'email' => $paymentInfo['email'] ?? '',
            'phone' => $paymentInfo['phone'] ?? '',
            'address' => $address['line1'] ?? 'Sri Lanka',
            'city' => $address['city'] ?? 'Colombo',
            'country' => 'Sri Lanka',
            'hash' => $this->generateHash($orderNumber, $amount),
        ];

        return [
            'mode' => 'form_redirect',
            'action' => $this->checkoutUrl(),
            'fields' => $fields,
        ];
    }

    public function verifyNotify(array $request): bool
    {
        $merchantId = (string)($request['merchant_id'] ?? '');
        $orderId = (string)($request['order_id'] ?? '');
        $amount = (string)($request['payhere_amount'] ?? '');
        $currency = (string)($request['payhere_currency'] ?? '');
        $statusCode = (string)($request['status_code'] ?? '');
        $md5sig = (string)($request['md5sig'] ?? '');

        if ($merchantId === '' || $orderId === '' || $md5sig === '') {
            return false;
        }

        $local = strtoupper(md5(
            $merchantId .
            $orderId .
            $amount .
            $currency .
            $statusCode .
            strtoupper(md5($this->cfg['merchant_secret']))
        ));

        return hash_equals($local, $md5sig) && $statusCode === '2';
    }

    public function verifyReturn(array $request): bool
    {
        // PayHere does not pass verified status on return_url — rely on notify.
        return false;
    }

    public function queryStatus(string $orderNumber): string
    {
        return 'UNKNOWN';
    }
}
