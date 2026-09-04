<?php

namespace App\Utilities;

use Exception;

class JsonSchemaValidator
{
    private static array $paymentGatewaySchema = [
        'type' => 'object',
        'properties' => [
            'status' => ['type' => 'string'],
            'transaction_id' => ['type' => 'string'],
            'amount' => ['type' => ['number', 'string']],
            'currency' => ['type' => 'string'],
            'timestamp' => ['type' => 'string'],
            'message' => ['type' => 'string'],
        ],
        'required' => ['status', 'transaction_id'],
    ];

    public static function validateGatewayResponse(array $data): bool
    {
        if (empty($data)) {
            return true;
        }

        if (!isset($data['status']) || !isset($data['transaction_id'])) {
            throw new Exception('Gateway response must contain status and transaction_id');
        }

        if (!is_string($data['status']) || !is_string($data['transaction_id'])) {
            throw new Exception('Gateway response fields must be strings');
        }

        if (strlen($data['transaction_id']) > 255) {
            throw new Exception('Transaction ID exceeds maximum length of 255 characters');
        }

        return true;
    }

    public static function sanitizeGatewayResponse(array $data): array
    {
        return [
            'status' => $data['status'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? null,
            'timestamp' => $data['timestamp'] ?? null,
            'message' => $data['message'] ?? null,
        ];
    }
}
