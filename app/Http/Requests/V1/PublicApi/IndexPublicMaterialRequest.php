<?php

namespace App\Http\Requests\V1\PublicApi;

use App\Enums\ContentLanguage;
use App\Enums\ExamStage;
use App\Enums\MaterialType;
use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexPublicMaterialRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return ['published_at', 'download_count', 'view_count', 'exam_year'];
    }

    protected function defaultSort(): string
    {
        return 'published_at';
    }

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
            'type' => ['nullable', Rule::enum(MaterialType::class)],
            'program' => ['nullable', 'string', 'max:80'],
            'level' => ['nullable', 'string', 'max:80'],
            'subject' => ['nullable', 'string', 'max:150'],
            'session' => ['nullable', 'string', 'max:20'],
            'exam_stage' => ['nullable', Rule::enum(ExamStage::class)],
            'exam_year' => ['nullable', 'integer', 'between:2000,2100'],
            'content_language' => ['nullable', Rule::enum(ContentLanguage::class)],
            'featured' => ['nullable', 'boolean'],
        ];
    }
}
