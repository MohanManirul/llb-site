<?php

namespace App\Http\Requests\V1\Admin\StudyMaterial;

use App\Enums\ContentLanguage;
use App\Enums\ContentStatus;
use App\Enums\ExamStage;
use App\Enums\MaterialType;
use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexStudyMaterialRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'title_bn', 'type', 'download_count', 'view_count', 'published_at', 'created_at'];
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
            'type' => ['nullable', Rule::enum(MaterialType::class)],
            'status' => ['nullable', Rule::enum(ContentStatus::class)],
            'subject_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')],
            'program_id' => ['nullable', 'integer', Rule::exists('programs', 'id')],
            'academic_session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')],
            'exam_stage' => ['nullable', Rule::enum(ExamStage::class)],
            'content_language' => ['nullable', Rule::enum(ContentLanguage::class)],
        ];
    }
}
