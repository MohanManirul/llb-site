<?php

namespace App\Http\Requests\V1\StudentApi;

use App\Enums\ExamStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PracticeQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'count' => ['nullable', 'integer', 'between:1,50'],
            'exam_stage' => ['nullable', Rule::enum(ExamStage::class)],
            'exam_year' => ['nullable', 'integer', 'between:1972,2100'],
        ];
    }
}
