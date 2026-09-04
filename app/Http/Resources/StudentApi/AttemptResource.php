<?php

namespace App\Http\Resources\StudentApi;

use App\Enums\AttemptStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttemptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'started_at' => $this->started_at?->toDateTimeString(),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
            'remaining_seconds' => $this->when(
                $this->status === AttemptStatus::InProgress,
                fn () => $this->remainingSeconds(),
            ),
            'score' => $this->score,
            'correct_count' => $this->correct_count,
            'wrong_count' => $this->wrong_count,
            'skipped_count' => $this->skipped_count,
            'model_test' => $this->whenLoaded('modelTest', fn () => [
                'id' => $this->modelTest->id,
                'slug' => $this->modelTest->slug,
                'title' => $this->modelTest->translated('title'),
                'duration_minutes' => $this->modelTest->duration_minutes,
                'negative_mark' => $this->modelTest->negative_mark,
            ]),
            'questions' => $this->when(
                $this->status === AttemptStatus::InProgress && $this->relationLoaded('modelTest'),
                fn () => $this->modelTest->questions()->with('options')->get()->map(fn ($question) => [
                    'id' => $question->id,
                    'question' => $question->translated('question'),
                    'marks' => $question->pivot->marks,
                    'options' => $question->options->map(fn ($option) => [
                        'id' => $option->id,
                        'option' => $option->translated('option'),
                    ])->all(),
                ])->all(),
            ),
            'answers' => $this->whenLoaded('answers', fn () => $this->answers
                ->pluck('question_option_id', 'question_id')
                ->all()),
        ];
    }
}
