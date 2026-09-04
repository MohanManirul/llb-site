<?php

namespace App\Http\Requests\V1\Admin\Question;

use App\Enums\ContentStatus;
use App\Enums\ExamStage;
use App\Enums\QuestionType;
use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexQuestionRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'exam_year', 'created_at'];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(QuestionType::class)],
            'status' => ['nullable', Rule::enum(ContentStatus::class)],
            'subject_id' => ['nullable', 'integer'],
            'program_id' => ['nullable', 'integer'],
            'exam_stage' => ['nullable', Rule::enum(ExamStage::class)],
            'exam_year' => ['nullable', 'integer'],
            'exclude_model_test' => ['nullable', 'integer'],
        ];
    }
}
