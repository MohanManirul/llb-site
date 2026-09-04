<?php

namespace App\Http\Resources\StudentApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentModelTestResource extends JsonResource
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
            'my_attempts' => $this->whenLoaded('attempts', fn () => $this->attempts->map(fn ($attempt) => [
                'id' => $attempt->id,
                'status' => $attempt->status,
                'score' => $attempt->score,
                'correct_count' => $attempt->correct_count,
                'wrong_count' => $attempt->wrong_count,
                'skipped_count' => $attempt->skipped_count,
                'started_at' => $attempt->started_at?->toDateTimeString(),
                'submitted_at' => $attempt->submitted_at?->toDateTimeString(),
            ])->all()),
        ];
    }
}
