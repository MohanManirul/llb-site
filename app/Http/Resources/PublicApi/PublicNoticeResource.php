<?php

namespace App\Http\Resources\PublicApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicNoticeResource extends JsonResource
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
            'excerpt' => $this->translated('excerpt', false),
            'category' => $this->category,
            'is_pinned' => $this->is_pinned,
            'published_at' => $this->published_at?->toDateTimeString(),
            'has_attachment' => $this->attachment_path !== null,
            'attachment_name' => $this->attachment_name,
            'attachment_size' => $this->attachment_size,
            'attachment_url' => $this->attachment_path !== null
                ? route('v1.public.notices.attachment', ['notice' => $this->slug])
                : null,
            'program' => $this->whenLoaded('program', fn () => $this->program
                ? ['slug' => $this->program->slug, 'name' => $this->program->translated('name')]
                : null),
            'session' => $this->whenLoaded('session', fn () => $this->session
                ? ['slug' => $this->session->slug, 'label' => $this->session->label]
                : null),
            'subject' => $this->whenLoaded('subject', fn () => $this->subject
                ? ['slug' => $this->subject->slug, 'name' => $this->subject->translated('name')]
                : null),
        ];
    }
}
