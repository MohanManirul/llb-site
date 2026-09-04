<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'program_id' => $this->program_id,
            'program_level_id' => $this->program_level_id,
            'code' => $this->code,
            'slug' => $this->slug,
            'name' => $this->translated('name'),
            'name_bn' => $this->name_bn,
            'name_en' => $this->name_en,
            'description_bn' => $this->description_bn,
            'description_en' => $this->description_en,
            'marks' => $this->marks,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'program' => new ProgramResource($this->whenLoaded('program')),
            'level' => new ProgramLevelResource($this->whenLoaded('level')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
