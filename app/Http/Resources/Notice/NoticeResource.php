<?php

namespace App\Http\Resources\Notice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoticeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->translated('title'),
            'title_bn' => $this->title_bn,
            'title_en' => $this->title_en,
            'excerpt_bn' => $this->excerpt_bn,
            'excerpt_en' => $this->excerpt_en,
            'body_bn' => $this->body_bn,
            'body_en' => $this->body_en,
            'category' => $this->category,
            'program_id' => $this->program_id,
            'program_level_id' => $this->program_level_id,
            'subject_id' => $this->subject_id,
            'academic_session_id' => $this->academic_session_id,
            'is_pinned' => $this->is_pinned,
            'status' => $this->status,
            'published_at' => $this->published_at?->toDateTimeString(),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'attachment_name' => $this->attachment_name,
            'attachment_size' => $this->attachment_size,
            'attachment_download_count' => $this->attachment_download_count,
            'program' => $this->whenLoaded('program', fn () => $this->program
                ? ['id' => $this->program->id, 'name_en' => $this->program->name_en]
                : null),
            'session' => $this->whenLoaded('session', fn () => $this->session
                ? ['id' => $this->session->id, 'label' => $this->session->label]
                : null),
            'subject' => $this->whenLoaded('subject', fn () => $this->subject
                ? ['id' => $this->subject->id, 'name_en' => $this->subject->name_en]
                : null),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
