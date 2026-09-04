<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'payment_id' => $this->payment_id,
            'action' => $this->action,
            'changed_amount' => $this->changed_amount ? (float) $this->changed_amount : null,
            'old_paid_amount' => $this->old_paid_amount ? (float) $this->old_paid_amount : null,
            'new_paid_amount' => $this->new_paid_amount ? (float) $this->new_paid_amount : null,
            'changed_by_name' => $this->changedBy?->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
