<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>|null
     */
    public function toArray(Request $request): ?array
    {
        if ($this->resource === null) {
            return null;
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'company_id' => $this->company_id,
            'company_name' => $this->company?->name,
            'department_id' => $this->department_id,
            'department_name' => $this->department?->name,
            'designation_id' => $this->designation_id,
            'designation' => $this->designation?->name,
            'name' => $this->user?->name,
            'email' => $this->user?->email,
            'phone' => $this->user?->phone,
            'description' => $this->description,
            'image_url' => $this->user?->image_url,
            'thumbnail_url' => $this->user?->thumbnail_url,
            'joining_date' => $this->joining_date?->toDateString(),
            'resignation_date' => $this->resignation_date?->toDateString(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
