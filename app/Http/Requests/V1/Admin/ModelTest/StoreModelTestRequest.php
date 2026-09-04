<?php

namespace App\Http\Requests\V1\Admin\ModelTest;

use App\Enums\ExamStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModelTestRequest extends FormRequest
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
            'title_bn' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description_bn' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')],
            'exam_stage' => ['nullable', Rule::enum(ExamStage::class)],
            'duration_minutes' => ['required', 'integer', 'between:5,600'],
            'negative_mark' => ['required', 'numeric', 'between:0,5'],
        ];
    }
}
