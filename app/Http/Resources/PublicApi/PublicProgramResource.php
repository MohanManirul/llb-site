<?php

namespace App\Http\Resources\PublicApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProgramResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->translated('name'),
            'short_name' => $this->translated('short_name', false),
            'has_levels' => $this->has_levels,
            'level_label' => $this->translated('level_label'),
            'has_exam_stages' => $this->has_exam_stages,
            'has_sessions' => $this->has_sessions,
            'subjects_count' => $this->whenCounted('subjects'),
            'levels' => $this->whenLoaded('levels', fn () => $this->levels->map(fn ($level) => [
                'slug' => $level->slug,
                'position' => $level->position,
                'name' => $level->translated('name'),
            ])),
        ];
    }
}
