<?php

namespace App\Http\Resources\StudentApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PracticeQuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->translated('question'),
            'explanation' => $this->translated('explanation', false),
            'exam_stage' => $this->exam_stage,
            'exam_year' => $this->exam_year,
            'reference' => $this->reference,
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($option) => [
                'id' => $option->id,
                'option' => $option->translated('option'),
                'is_correct' => $option->is_correct,
            ])->all()),
        ];
    }
}
