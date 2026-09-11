<?php

namespace App\Http\Resources\StudentApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassNoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->translated('title'),
            'description' => $this->translated('description'),
            'published_at' => $this->published_at?->toDateTimeString(),
            'has_attachment' => $this->attachment_path !== null,
            'attachment_name' => $this->attachment_name,
            'attachment_size' => $this->attachment_size,
            'subject' => $this->whenLoaded('subject', fn () => $this->subject
                ? ['id' => $this->subject->id, 'name' => $this->subject->translated('name')]
                : null),
            'session' => $this->whenLoaded('session', fn () => $this->session
                ? ['id' => $this->session->id, 'label' => $this->session->label]
                : null),
            'teacher' => $this->whenLoaded('teacher', fn () => $this->teacher?->name),
        ];
    }
}
