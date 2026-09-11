<?php

namespace App\Http\Requests\V1\StudentApi;

use App\Enums\NoticeCategory;
use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexCollegeContentRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'published_at', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'published_at';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'category' => ['nullable', Rule::enum(NoticeCategory::class)],
            'subject_id' => ['nullable', 'integer'],
        ];
    }
}
