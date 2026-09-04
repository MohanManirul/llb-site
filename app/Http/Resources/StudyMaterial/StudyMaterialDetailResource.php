<?php

namespace App\Http\Resources\StudyMaterial;

use Illuminate\Http\Request;

class StudyMaterialDetailResource extends StudyMaterialResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'files' => MaterialFileResource::collection($this->whenLoaded('files')),
        ];
    }
}
