<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'payment_type' => $this->payment_type?->label(),
            'amount' => (float) $this->amount,
            'payment_date' => $this->payment_date?->toDateString(),
            'reference_number' => $this->reference_number,
            'proof' => $this->proof,
            'notes' => $this->notes,
            'created_by' => $this->createdBy?->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
