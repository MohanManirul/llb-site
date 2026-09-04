<?php

namespace App\Http\Resources\StudyMaterial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'study_material_id' => $this->study_material_id,
            'original_name' => $this->original_name,
            'label' => $this->translated('label', false),
            'label_bn' => $this->label_bn,
            'label_en' => $this->label_en,
            'size' => $this->size,
            'page_count' => $this->page_count,
            'sort_order' => $this->sort_order,
            'download_count' => $this->download_count,
            'preview_url' => route('v1.admin.study-materials.files.preview', [
                'studyMaterial' => $this->study_material_id,
                'file' => $this->id,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
