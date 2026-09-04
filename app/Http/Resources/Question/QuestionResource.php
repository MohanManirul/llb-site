<?php

namespace App\Http\Resources\Question;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'subject_id' => $this->subject_id,
            'subject' => $this->whenLoaded('subject', fn () => [
                'id' => $this->subject->id,
                'name_bn' => $this->subject->name_bn,
                'name_en' => $this->subject->name_en,
                'program' => $this->subject->relationLoaded('program') ? [
                    'id' => $this->subject->program->id,
                    'slug' => $this->subject->program->slug,
                    'name_bn' => $this->subject->program->name_bn,
                    'name_en' => $this->subject->program->name_en,
                ] : null,
            ]),
            'exam_stage' => $this->exam_stage,
            'exam_year' => $this->exam_year,
            'question' => $this->translated('question'),
            'question_bn' => $this->question_bn,
            'question_en' => $this->question_en,
            'reference' => $this->reference,
            'status' => $this->status,
            'options_count' => $this->whenCounted('options'),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
