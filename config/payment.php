<?php

return [
    'next_payment_days' => env('PAYMENT_NEXT_DAYS', 30),
    'notification_days_before' => env('PAYMENT_NOTIFY_DAYS', 7),
    'proof_disk' => env('PAYMENT_PROOF_DISK', 'public'),

    'gateways' => [
        'ssl_commerz' => [
            'store_id' => env('SSL_COMMERZ_STORE_ID'),
            'store_password' => env('SSL_COMMERZ_STORE_PASSWORD'),
            'currency' => env('SSL_COMMERZ_CURRENCY', 'BDT'),
            'test_mode' => env('SSL_COMMERZ_TEST_MODE', true),
        ],
    ],
];
