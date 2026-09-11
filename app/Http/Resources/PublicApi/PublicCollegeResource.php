<?php

namespace App\Http\Resources\PublicApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicCollegeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => $this->name_en ?? $this->name_bn,
            'label_bn' => $this->name_bn,
            'slug' => $this->slug,
            'district' => $this->translated('district'),
        ];
    }
}
