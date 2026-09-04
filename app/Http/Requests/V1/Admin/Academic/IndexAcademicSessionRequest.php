<?php

namespace App\Http\Requests\V1\Admin\Academic;

use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class IndexAcademicSessionRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'label', 'start_year', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'start_year';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
