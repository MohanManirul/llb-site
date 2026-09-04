<?php

namespace App\Http\Requests\V1\Admin\ActivityLog;

use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class IndexActivityLogRequest extends IndexRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'type' => ['nullable', 'string', 'max:255'],
            'subject_type' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return [
            'type', 'subject_type', 'created_at',
        ];
    }

    protected function defaultSort(): string
    {
        return 'created_at';
    }
}
