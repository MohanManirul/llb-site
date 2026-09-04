<?php

namespace App\Http\Resources\PublicApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicMaterialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'slug' => $this->slug,
            'title' => $this->translated('title'),
            'description' => $this->translated('description', false),
            'exam_stage' => $this->exam_stage,
            'exam_year' => $this->exam_year,
            'content_language' => $this->content_language,
            'author' => $this->author,
            'publisher' => $this->publisher,
            'edition' => $this->edition,
            'page_count' => $this->page_count,
            'cover_url' => $this->cover_url,
            'cover_thumbnail_url' => $this->cover_thumbnail_url,
            'is_featured' => $this->is_featured,
            'view_count' => $this->view_count,
            'download_count' => $this->download_count,
            'files_count' => $this->whenCounted('files'),
            'published_at' => $this->published_at?->toDateTimeString(),
            'subject' => $this->whenLoaded('subject', fn () => [
                'slug' => $this->subject->slug,
                'name' => $this->subject->translated('name'),
                'program' => $this->subject->relationLoaded('program') && $this->subject->program
                    ? [
                        'slug' => $this->subject->program->slug,
                        'name' => $this->subject->program->translated('name'),
                        'short_name' => $this->subject->program->translated('short_name', false),
                    ]
                    : null,
                'level' => $this->subject->relationLoaded('level') && $this->subject->level
                    ? [
                        'slug' => $this->subject->level->slug,
                        'name' => $this->subject->level->translated('name'),
                    ]
                    : null,
            ]),
            'session' => $this->whenLoaded('session', fn () => $this->session
                ? ['slug' => $this->session->slug, 'label' => $this->session->label]
                : null),
        ];
    }
}
