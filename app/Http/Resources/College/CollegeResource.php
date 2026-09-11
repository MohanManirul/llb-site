<?php

namespace App\Http\Resources\College;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollegeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->translated('name'),
            'name_bn' => $this->name_bn,
            'name_en' => $this->name_en,
            'short_name_bn' => $this->short_name_bn,
            'short_name_en' => $this->short_name_en,
            'eiin_code' => $this->eiin_code,
            'college_code' => $this->college_code,
            'district_bn' => $this->district_bn,
            'district_en' => $this->district_en,
            'address_bn' => $this->address_bn,
            'address_en' => $this->address_en,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'students_count' => $this->whenCounted('students'),
            'teachers_count' => $this->whenCounted('teachers'),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
