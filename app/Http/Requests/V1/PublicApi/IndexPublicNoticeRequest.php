<?php

namespace App\Http\Requests\V1\PublicApi;

use App\Enums\NoticeCategory;
use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexPublicNoticeRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return ['published_at'];
    }

    protected function defaultSort(): string
    {
        return 'published_at';
    }

    protected function defaultPerPage(): int
    {
        return 15;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'category' => ['nullable', Rule::enum(NoticeCategory::class)],
            'program' => ['nullable', 'string', 'max:80'],
            'session' => ['nullable', 'string', 'max:20'],
        ];
    }
}
