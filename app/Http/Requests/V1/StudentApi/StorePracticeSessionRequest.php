<?php

namespace App\Http\Requests\V1\StudentApi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePracticeSessionRequest extends FormRequest
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
            'question_count' => ['required', 'integer', 'between:1,200'],
            'correct_count' => ['required', 'integer', 'min:0', 'lte:question_count'],
        ];
    }
}
