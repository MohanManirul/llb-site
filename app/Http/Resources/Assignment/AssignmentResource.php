<?php

namespace App\Http\Resources\Assignment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team' => $this->team?->name,
            'employee' => $this->employee?->user?->name,
            'assigned_at' => $this->assigned_at?->toDateTimeString(),
            'unassigned_at' => $this->unassigned_at?->toDateTimeString(),
            'reason' => $this->reason?->value,
        ];
    }
}
