<?php

namespace App\Http\Resources\Question;

use Illuminate\Http\Request;

class QuestionDetailResource extends QuestionResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'explanation_bn' => $this->explanation_bn,
            'explanation_en' => $this->explanation_en,
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($option) => [
                'id' => $option->id,
                'option_bn' => $option->option_bn,
                'option_en' => $option->option_en,
                'is_correct' => $option->is_correct,
                'sort_order' => $option->sort_order,
            ])->all()),
        ];
    }
}
