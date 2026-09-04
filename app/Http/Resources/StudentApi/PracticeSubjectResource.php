<?php

namespace App\Http\Resources\StudentApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PracticeSubjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->translated('name'),
            'question_count' => (int) $this->question_count,
            'program' => $this->whenLoaded('program', fn () => [
                'id' => $this->program->id,
                'slug' => $this->program->slug,
                'name' => $this->program->translated('name'),
            ]),
            'level' => $this->whenLoaded('level', fn () => $this->level === null ? null : [
                'id' => $this->level->id,
                'name' => $this->level->translated('name'),
            ]),
        ];
    }
}
