<?php

namespace App\Http\Requests\V1\Admin\Notice;

use App\Enums\ContentStatus;
use App\Enums\NoticeCategory;
use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexNoticeRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'title_bn', 'category', 'published_at', 'created_at'];
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
            'category' => ['nullable', Rule::enum(NoticeCategory::class)],
            'status' => ['nullable', Rule::enum(ContentStatus::class)],
            'program_id' => ['nullable', 'integer', Rule::exists('programs', 'id')],
            'academic_session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')],
        ];
    }
}
