<?php

namespace App\Http\Resources\ModelTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModelTestResource extends JsonResource
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
            'title_bn' => $this->title_bn,
            'title_en' => $this->title_en,
            'program_id' => $this->program_id,
            'program' => $this->whenLoaded('program', fn () => [
                'id' => $this->program->id,
                'slug' => $this->program->slug,
                'name_bn' => $this->program->name_bn,
                'name_en' => $this->program->name_en,
            ]),
            'exam_stage' => $this->exam_stage,
            'duration_minutes' => $this->duration_minutes,
            'negative_mark' => $this->negative_mark,
            'status' => $this->status,
            'published_at' => $this->published_at?->toDateTimeString(),
            'questions_count' => $this->whenCounted('questions'),
            'attempts_count' => $this->whenCounted('attempts'),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
