<?php

namespace App\Http\Requests\V1\Admin\Question;

use App\Enums\ExamStage;
use App\Enums\QuestionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
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
            'type' => ['required', Rule::enum(QuestionType::class)],
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'exam_stage' => ['nullable', Rule::enum(ExamStage::class)],
            'exam_year' => ['nullable', 'integer', 'between:1972,2100'],
            'question_bn' => ['required', 'string'],
            'question_en' => ['nullable', 'string'],
            'explanation_bn' => ['nullable', 'string'],
            'explanation_en' => ['nullable', 'string'],
            'reference' => ['nullable', 'string', 'max:255'],
            'options' => [
                Rule::requiredIf($this->input('type') === QuestionType::Mcq->value),
                Rule::prohibitedIf($this->input('type') === QuestionType::Written->value),
                'array', 'min:2', 'max:5',
            ],
            'options.*.id' => ['nullable', 'integer', Rule::exists('question_options', 'id')],
            'options.*.option_bn' => ['required', 'string'],
            'options.*.option_en' => ['nullable', 'string'],
            'options.*.is_correct' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('type') !== QuestionType::Mcq->value) {
                return;
            }

            $correct = collect($this->input('options', []))
                ->filter(fn ($option) => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN))
                ->count();

            if ($correct !== 1) {
                $validator->errors()->add('options', 'Exactly one option must be marked as correct.');
            }
        });
    }
}
