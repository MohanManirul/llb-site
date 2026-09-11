<?php

namespace App\Http\Resources\Teacher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
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
            'designation_bn' => $this->designation_bn,
            'designation_en' => $this->designation_en,
            'college' => $this->whenLoaded('college', fn () => $this->college === null ? null : [
                'id' => $this->college->id,
                'slug' => $this->college->slug,
                'name_bn' => $this->college->name_bn,
                'name_en' => $this->college->name_en,
            ]),
            'is_active' => $this->is_active,
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'approved_by' => $this->whenLoaded('approver', fn () => $this->approver?->name),
            'notices_count' => $this->whenCounted('notices'),
            'class_routines_count' => $this->whenCounted('classRoutines'),
            'class_notes_count' => $this->whenCounted('classNotes'),
            'last_login_at' => $this->last_login_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
