<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->translated('name'),
            'name_bn' => $this->name_bn,
            'name_en' => $this->name_en,
            'short_name_bn' => $this->short_name_bn,
            'short_name_en' => $this->short_name_en,
            'has_levels' => $this->has_levels,
            'level_label' => $this->translated('level_label'),
            'level_label_bn' => $this->level_label_bn,
            'level_label_en' => $this->level_label_en,
            'has_exam_stages' => $this->has_exam_stages,
            'has_sessions' => $this->has_sessions,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'levels_count' => $this->whenCounted('levels'),
            'subjects_count' => $this->whenCounted('subjects'),
            'levels' => ProgramLevelResource::collection($this->whenLoaded('levels')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
