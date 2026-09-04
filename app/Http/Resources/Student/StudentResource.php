<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'program' => $this->whenLoaded('program', fn () => $this->program === null ? null : [
                'id' => $this->program->id,
                'slug' => $this->program->slug,
                'name_bn' => $this->program->name_bn,
                'name_en' => $this->program->name_en,
            ]),
            'is_active' => $this->is_active,
            'attempts_count' => $this->whenCounted('attempts'),
            'practice_sessions_count' => $this->whenCounted('practiceSessions'),
            'last_login_at' => $this->last_login_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
