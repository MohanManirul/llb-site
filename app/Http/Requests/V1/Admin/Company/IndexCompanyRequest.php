<?php

namespace App\Http\Requests\V1\Admin\Company;

use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class IndexCompanyRequest extends IndexRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'is_active' => ['nullable', 'in:0,1'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return [
            'name', 'email', 'is_active',
        ];
    }
}
