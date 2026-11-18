<?php

require_once __DIR__ . '/PaymentGatewayInterface.php';
require_once __DIR__ . '/DirectPayGateway.php';
require_once __DIR__ . '/PayHereGateway.php';
require_once __DIR__ . '/ApiRedirectGateways.php';
require_once __DIR__ . '/../../helpers/payment.php';

class PaymentGatewayFactory
{
    public static function make(string $gateway): ?PaymentGatewayInterface
    {
        switch ($gateway) {
            case 'directpay':
                return new DirectPayGateway();
            case 'payhere':
                return new PayHereGateway();
            case 'payzy':
                return new PayzyGateway();
            case 'paykoko':
                return new PayKokoGateway();
            default:
                return null;
        }
    }

    public static function resolve(?string $paymentMethod = null): ?PaymentGatewayInterface
    {
        $gatewayId = ezymart_payment_resolve_gateway($paymentMethod);
        return $gatewayId ? self::make($gatewayId) : null;
    }
}
