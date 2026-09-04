<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_amount' => (float) $this['total_amount'],
            'paid_amount' => (float) $this['paid_amount'],
            'due_amount' => (float) $this['due_amount'],
            'next_payment_date' => $this['next_payment_date'],
            'last_payment_date' => $this['last_payment_date'],
            'payment_count' => $this['payment_count'],
        ];
    }
}
