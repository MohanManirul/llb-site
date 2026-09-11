<?php

namespace App\Http\Resources\TeacherApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherProfileResource extends JsonResource
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
            'is_active' => $this->is_active,
            'college_id' => $this->college_id,
            'college' => $this->whenLoaded('college', fn () => $this->college === null ? null : [
                'id' => $this->college->id,
                'slug' => $this->college->slug,
                'name' => $this->college->translated('name'),
            ]),
            'last_login_at' => $this->last_login_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
