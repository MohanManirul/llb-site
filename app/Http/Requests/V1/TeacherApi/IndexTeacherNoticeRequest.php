<?php

namespace App\Http\Requests\V1\TeacherApi;

use App\Enums\ContentStatus;
use App\Enums\NoticeCategory;
use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexTeacherNoticeRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'title_en', 'published_at', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'created_at';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(ContentStatus::class)],
            'category' => ['nullable', Rule::enum(NoticeCategory::class)],
        ];
    }
}
