<?php

namespace App\Http\Resources\Milestone;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MilestoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sequence' => $this->sequence,
            'label' => $this->label,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'target_amount' => $this->target_amount,
            'achieved_amount' => $this->achieved_amount,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
        ];
    }
}
