<?php

namespace App\Http\Requests\V1\Admin\Designation;

use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class IndexDesignationRequest extends IndexRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'is_active' => ['nullable', 'in:active,inactive'],
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
