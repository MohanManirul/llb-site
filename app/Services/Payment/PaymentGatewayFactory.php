<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Gateways\ManualPaymentGateway;
use App\Services\Payment\Gateways\SSLCommerzGateway;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    private static array $gateways = [
        'cash' => ManualPaymentGateway::class,
        'bank_transfer' => ManualPaymentGateway::class,
        'cheque' => ManualPaymentGateway::class,
        'card' => ManualPaymentGateway::class,
        'mobile_wallet' => ManualPaymentGateway::class,
        'other' => ManualPaymentGateway::class,
        'ssl_gateway' => SSLCommerzGateway::class,
    ];

    public static function resolve(string $paymentType): PaymentGatewayInterface
    {
        $gatewayClass = self::$gateways[$paymentType] ?? null;

        if (! $gatewayClass) {
            throw new InvalidArgumentException("Unsupported payment type: {$paymentType}");
        }

        $config = config('payment.gateways.'.$paymentType, []);

        return app($gatewayClass)->initialize($config);
    }
}
