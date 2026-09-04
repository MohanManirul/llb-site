<?php

namespace App\Contracts;

use App\Data\PaymentResponse;

interface PaymentGatewayInterface
{
    public function initialize(array $config): self;

    public function charge(float $amount, array $details): PaymentResponse;

    public function verify(array $webhookData): PaymentResponse;

    public function getName(): string;

    public function isEnabled(): bool;
}
