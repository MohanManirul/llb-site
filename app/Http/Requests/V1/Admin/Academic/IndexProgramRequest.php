<?php

namespace App\Http\Requests\V1\Admin\Academic;

use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class IndexProgramRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'name_en', 'sort_order', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'sort_order';
    }

    protected function defaultDirection(): string
    {
        return 'asc';
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
