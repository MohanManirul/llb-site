<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramLevelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'program_id' => $this->program_id,
            'position' => $this->position,
            'slug' => $this->slug,
            'name' => $this->translated('name'),
            'name_bn' => $this->name_bn,
            'name_en' => $this->name_en,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
