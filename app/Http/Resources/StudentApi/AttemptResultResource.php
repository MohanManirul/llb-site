<?php

namespace App\Http\Resources\StudentApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttemptResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $answers = $this->answers->keyBy('question_id');

        return [
            'id' => $this->id,
            'status' => $this->status,
            'started_at' => $this->started_at?->toDateTimeString(),
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
            'score' => $this->score,
            'correct_count' => $this->correct_count,
            'wrong_count' => $this->wrong_count,
            'skipped_count' => $this->skipped_count,
            'model_test' => [
                'id' => $this->modelTest->id,
                'slug' => $this->modelTest->slug,
                'title' => $this->modelTest->translated('title'),
                'duration_minutes' => $this->modelTest->duration_minutes,
                'negative_mark' => $this->modelTest->negative_mark,
                'total_marks' => $this->modelTest->totalMarks(),
                'program' => $this->modelTest->relationLoaded('program') ? [
                    'id' => $this->modelTest->program->id,
                    'slug' => $this->modelTest->program->slug,
                    'name' => $this->modelTest->program->translated('name'),
                ] : null,
            ],
            'breakdown' => $this->modelTest->questions->map(function ($question) use ($answers) {
                $answer = $answers->get($question->id);

                return [
                    'id' => $question->id,
                    'question' => $question->translated('question'),
                    'explanation' => $question->translated('explanation', false),
                    'reference' => $question->reference,
                    'marks' => $question->pivot->marks,
                    'options' => $question->options->map(fn ($option) => [
                        'id' => $option->id,
                        'option' => $option->translated('option'),
                        'is_correct' => $option->is_correct,
                    ])->all(),
                    'chosen_option_id' => $answer?->question_option_id,
                    'is_correct' => $answer?->is_correct,
                ];
            })->all(),
        ];
    }
}
