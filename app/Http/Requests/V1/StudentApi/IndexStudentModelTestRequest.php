<?php

namespace App\Http\Requests\V1\StudentApi;

use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class IndexStudentModelTestRequest extends IndexRequest
{
    protected function defaultPerPage(): int
    {
        return 12;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'program_id' => ['nullable', 'integer'],
        ];
    }
}
