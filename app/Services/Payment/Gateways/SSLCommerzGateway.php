<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Data\PaymentResponse;
use Illuminate\Support\Facades\Http;

class SSLCommerzGateway implements PaymentGatewayInterface
{
    private array $config = [];

    private string $baseUrl = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';

    public function initialize(array $config): self
    {
        $this->config = $config;

        if (! ($config['test_mode'] ?? true)) {
            $this->baseUrl = 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';
        }

        return $this;
    }

    public function charge(float $amount, array $details): PaymentResponse
    {
        $payload = [
            'store_id' => $this->config['store_id'],
            'store_passwd' => $this->config['store_password'],
            'total_amount' => $amount,
            'currency' => $this->config['currency'] ?? 'BDT',
            'tran_id' => 'TXN-'.uniqid(),
            'success_url' => route('payment.ssl.success'),
            'fail_url' => route('payment.ssl.fail'),
            'cancel_url' => route('payment.ssl.cancel'),
            'cus_name' => $details['customer_name'] ?? 'Customer',
            'cus_email' => $details['customer_email'] ?? 'customer@example.com',
            'cus_phone' => $details['customer_phone'] ?? '',
            'product_name' => $details['product_name'] ?? 'Product',
            'product_category' => $details['product_category'] ?? 'Payment',
            'product_profile' => $details['product_profile'] ?? 'general',
        ];

        try {
            $response = Http::timeout(30)->asForm()->post($this->baseUrl, $payload);

            if ($response->successful()) {
                $data = $this->parseResponse($response->body());

                return PaymentResponse::success($data['tran_id'] ?? $payload['tran_id'], $data);
            }

            return PaymentResponse::failed('SSL Commerz gateway error: '.$response->status());
        } catch (\Exception $e) {
            return PaymentResponse::failed('SSL Commerz connection failed: '.$e->getMessage());
        }
    }

    public function verify(array $webhookData): PaymentResponse
    {
        $payload = [
            'store_id' => $this->config['store_id'],
            'store_passwd' => $this->config['store_password'],
            'val_id' => $webhookData['val_id'] ?? '',
        ];

        try {
            $url = str_replace('api.php', 'validate_transaction.php', $this->baseUrl);
            $response = Http::timeout(30)->asForm()->post($url, $payload);

            if ($response->successful() && str_contains($response->body(), 'VALID')) {
                return PaymentResponse::success($webhookData['tran_id'] ?? '', $webhookData);
            }

            return PaymentResponse::failed('Transaction validation failed');
        } catch (\Exception $e) {
            return PaymentResponse::failed('Validation error: '.$e->getMessage());
        }
    }

    public function getName(): string
    {
        return 'SSL Commerz Gateway';
    }

    public function isEnabled(): bool
    {
        return ! empty($this->config['store_id']) && ! empty($this->config['store_password']);
    }

    private function parseResponse(string $response): array
    {
        $data = [];
        parse_str($response, $data);

        return $data;
    }
}
