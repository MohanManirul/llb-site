<?php

namespace App\Data;

readonly class PaymentResponse
{
    public function __construct(
        public bool $success,
        public string $transactionId,
        public ?string $message = null,
        public ?array $metadata = null,
    ) {}

    public static function success(string $transactionId, ?array $metadata = null): self
    {
        return new self(
            success: true,
            transactionId: $transactionId,
            metadata: $metadata,
        );
    }

    public static function failed(string $message, ?array $metadata = null): self
    {
        return new self(
            success: false,
            transactionId: '',
            message: $message,
            metadata: $metadata,
        );
    }
}
