<?php

namespace App\Http\Resources\PublicApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArchiveQuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'question' => $this->translated('question'),
            'explanation' => $this->translated('explanation', false),
            'reference' => $this->reference,
            'exam_stage' => $this->exam_stage,
            'exam_year' => $this->exam_year,
            'subject' => $this->whenLoaded('subject', fn () => [
                'id' => $this->subject->id,
                'slug' => $this->subject->slug,
                'name' => $this->subject->translated('name'),
                'program' => $this->subject->relationLoaded('program') ? [
                    'slug' => $this->subject->program->slug,
                    'name' => $this->subject->program->translated('name'),
                ] : null,
            ]),
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($option) => [
                'id' => $option->id,
                'option' => $option->translated('option'),
                'is_correct' => $option->is_correct,
            ])->all()),
        ];
    }
}
