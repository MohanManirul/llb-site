<?php

namespace App\Http\Resources\StudyMaterial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudyMaterialResource extends JsonResource
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
            'title_bn' => $this->title_bn,
            'title_en' => $this->title_en,
            'description_bn' => $this->description_bn,
            'description_en' => $this->description_en,
            'subject_id' => $this->subject_id,
            'academic_session_id' => $this->academic_session_id,
            'exam_stage' => $this->exam_stage,
            'exam_year' => $this->exam_year,
            'content_language' => $this->content_language,
            'author' => $this->author,
            'publisher' => $this->publisher,
            'edition' => $this->edition,
            'page_count' => $this->page_count,
            'cover_url' => $this->cover_url,
            'cover_thumbnail_url' => $this->cover_thumbnail_url,
            'status' => $this->status,
            'published_at' => $this->published_at?->toDateTimeString(),
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'view_count' => $this->view_count,
            'download_count' => $this->download_count,
            'files_count' => $this->whenCounted('files'),
            'subject' => $this->whenLoaded('subject', fn () => [
                'id' => $this->subject->id,
                'name_bn' => $this->subject->name_bn,
                'name_en' => $this->subject->name_en,
                'program' => $this->subject->relationLoaded('program') && $this->subject->program
                    ? [
                        'id' => $this->subject->program->id,
                        'name_en' => $this->subject->program->name_en,
                        'slug' => $this->subject->program->slug,
                    ]
                    : null,
            ]),
            'session' => $this->whenLoaded('session', fn () => [
                'id' => $this->session->id,
                'label' => $this->session->label,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
