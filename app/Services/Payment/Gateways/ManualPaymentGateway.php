<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Data\PaymentResponse;

class ManualPaymentGateway implements PaymentGatewayInterface
{
    private array $config = [];

    public function initialize(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function charge(float $amount, array $details): PaymentResponse
    {
        $transactionId = 'MANUAL-'.uniqid();

        return PaymentResponse::success($transactionId, [
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'type' => 'manual_payment',
            'amount' => $amount,
            'payment_method' => $details['payment_type'] ?? 'unknown',
            'reference' => $details['reference_number'] ?? null,
        ]);
    }

    public function verify(array $webhookData): PaymentResponse
    {
        return PaymentResponse::success($webhookData['transaction_id'] ?? '', $webhookData);
    }

    public function getName(): string
    {
        return 'Manual Payment Gateway';
    }

    public function isEnabled(): bool
    {
        return true;
    }
}
