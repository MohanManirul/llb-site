<?php

namespace App\Http\Requests\V1\Admin\ModelTest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderQuestionsRequest extends FormRequest
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
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'distinct'],
            'marks' => ['nullable', 'array'],
            'marks.*' => ['numeric', 'between:0.25,100'],
        ];
    }
}
