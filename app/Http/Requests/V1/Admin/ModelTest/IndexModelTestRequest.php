<?php

namespace App\Http\Requests\V1\Admin\ModelTest;

use App\Enums\ContentStatus;
use App\Enums\ExamStage;
use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexModelTestRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'created_at', 'published_at'];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(ContentStatus::class)],
            'program_id' => ['nullable', 'integer'],
            'exam_stage' => ['nullable', Rule::enum(ExamStage::class)],
        ];
    }
}
