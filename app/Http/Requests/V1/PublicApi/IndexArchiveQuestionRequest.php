<?php

namespace App\Http\Requests\V1\PublicApi;

use App\Enums\ExamStage;
use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexArchiveQuestionRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'exam_year'];
    }

    protected function defaultSort(): string
    {
        return 'exam_year';
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
            'program' => ['nullable', 'string', 'max:80'],
            'subject' => ['nullable', 'string', 'max:150'],
            'exam_stage' => ['nullable', Rule::enum(ExamStage::class)],
            'exam_year' => ['nullable', 'integer'],
        ];
    }
}
