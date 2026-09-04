<?php

namespace App\Http\Resources\PublicApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicSubjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'code' => $this->code,
            'name' => $this->translated('name'),
            'description' => $this->translated('description', false),
            'marks' => $this->marks,
            'suggestions_count' => $this->whenHas('suggestions_count'),
            'books_count' => $this->whenHas('books_count'),
            'notes_count' => $this->whenHas('notes_count'),
            'program' => $this->whenLoaded('program', fn () => [
                'slug' => $this->program->slug,
                'name' => $this->program->translated('name'),
                'has_levels' => $this->when(isset($this->program->has_levels), $this->program->has_levels),
                'has_exam_stages' => $this->when(isset($this->program->has_exam_stages), $this->program->has_exam_stages),
                'has_sessions' => $this->when(isset($this->program->has_sessions), $this->program->has_sessions),
                'level_label' => $this->program->translated('level_label'),
            ]),
            'level' => $this->whenLoaded('level', fn () => $this->level
                ? ['slug' => $this->level->slug, 'name' => $this->level->translated('name')]
                : null),
        ];
    }
}
