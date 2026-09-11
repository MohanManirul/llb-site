<?php

namespace App\Http\Requests\V1\Admin\Teacher;

use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class IndexTeacherRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'created_at', 'last_login_at'];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'is_active' => ['nullable', 'boolean'],
            'college_id' => ['nullable', 'integer'],
        ];
    }
}
