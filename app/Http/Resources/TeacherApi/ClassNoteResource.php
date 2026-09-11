<?php

namespace App\Http\Resources\TeacherApi;

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
            'title_bn' => $this->title_bn,
            'title_en' => $this->title_en,
            'description_bn' => $this->description_bn,
            'description_en' => $this->description_en,
            'college_id' => $this->college_id,
            'subject_id' => $this->subject_id,
            'program_id' => $this->program_id,
            'program_level_id' => $this->program_level_id,
            'academic_session_id' => $this->academic_session_id,
            'status' => $this->status,
            'published_at' => $this->published_at?->toDateTimeString(),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'attachment_name' => $this->attachment_name,
            'attachment_size' => $this->attachment_size,
            'subject' => $this->whenLoaded('subject', fn () => $this->subject
                ? ['id' => $this->subject->id, 'name_bn' => $this->subject->name_bn, 'name_en' => $this->subject->name_en]
                : null),
            'session' => $this->whenLoaded('session', fn () => $this->session
                ? ['id' => $this->session->id, 'label' => $this->session->label]
                : null),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
