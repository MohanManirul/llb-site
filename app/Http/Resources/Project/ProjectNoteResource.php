<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectNoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'note' => $this->note,
            'author' => $this->whenLoaded('user', fn () => $this->user?->name),
            'created_at' => $this->created_at?->toDateTimeString(),
            'created_date' => $this->created_at?->toDateString(),
        ];
    }
}
