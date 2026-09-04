<?php

namespace App\Http\Resources\ModelTest;

use Illuminate\Http\Request;

class ModelTestDetailResource extends ModelTestResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'description_bn' => $this->description_bn,
            'description_en' => $this->description_en,
            'total_marks' => $this->whenLoaded('questions', fn () => (float) $this->questions->sum('pivot.marks')),
            'questions' => $this->whenLoaded('questions', fn () => $this->questions->map(fn ($question) => [
                'id' => $question->id,
                'type' => $question->type,
                'question_bn' => $question->question_bn,
                'question_en' => $question->question_en,
                'exam_year' => $question->exam_year,
                'status' => $question->status,
                'sort_order' => $question->pivot->sort_order,
                'marks' => $question->pivot->marks,
                'options' => $question->relationLoaded('options') ? $question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'option_bn' => $option->option_bn,
                    'option_en' => $option->option_en,
                    'is_correct' => $option->is_correct,
                ])->all() : [],
            ])->all()),
        ];
    }
}
