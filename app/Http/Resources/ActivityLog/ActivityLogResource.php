<?php

namespace App\Http\Resources\ActivityLog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'description' => $this->description,
            'subject_type' => $this->subject_type ? class_basename($this->subject_type) : null,
            'subject_id' => $this->subject_id,
            'causer' => $this->whenLoaded('causer', fn () => $this->causer?->name ?? $this->causer?->email),
            'impersonator' => $this->whenLoaded('impersonator', fn () => $this->impersonator?->name ?? $this->impersonator?->email),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
