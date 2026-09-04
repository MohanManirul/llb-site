<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_date' => $this->sale_date?->toDateString(),
            'amount' => $this->amount,
            'reference' => $this->reference,
            'employee' => $this->employee?->user?->name,
            'notes' => $this->notes,
        ];
    }
}
