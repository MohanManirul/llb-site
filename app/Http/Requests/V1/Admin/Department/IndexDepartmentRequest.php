<?php

namespace App\Http\Requests\V1\Admin\Department;

use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class IndexDepartmentRequest extends IndexRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'is_active' => ['nullable', 'in:active,inactive'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return [
            'name', 'is_active', 'created_at',
        ];
    }
}
