<?php

namespace App\Http\Resources\StudentApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PracticeSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_count' => $this->question_count,
            'correct_count' => $this->correct_count,
            'subject' => $this->whenLoaded('subject', fn () => [
                'id' => $this->subject->id,
                'slug' => $this->subject->slug,
                'name' => $this->subject->translated('name'),
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
