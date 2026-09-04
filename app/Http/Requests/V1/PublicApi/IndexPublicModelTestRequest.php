<?php

namespace App\Http\Requests\V1\PublicApi;

use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class IndexPublicModelTestRequest extends IndexRequest
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
            'program' => ['nullable', 'string', 'max:80'],
        ];
    }
}
