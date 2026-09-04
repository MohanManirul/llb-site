<?php

namespace App\Http\Resources\PublicApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicModelTestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->translated('title'),
            'description' => $this->translated('description', false),
            'exam_stage' => $this->exam_stage,
            'duration_minutes' => $this->duration_minutes,
            'negative_mark' => $this->negative_mark,
            'question_count' => $this->whenCounted('questions'),
            'published_at' => $this->published_at?->toDateTimeString(),
            'program' => $this->whenLoaded('program', fn () => [
                'id' => $this->program->id,
                'slug' => $this->program->slug,
                'name' => $this->program->translated('name'),
            ]),
        ];
    }
}
