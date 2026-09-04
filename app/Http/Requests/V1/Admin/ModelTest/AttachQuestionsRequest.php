<?php

namespace App\Http\Requests\V1\Admin\ModelTest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachQuestionsRequest extends FormRequest
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
            'question_ids' => ['required', 'array', 'min:1', 'max:200'],
            'question_ids.*' => ['integer', 'distinct', Rule::exists('questions', 'id')],
            'marks' => ['nullable', 'numeric', 'between:0.25,100'],
        ];
    }
}
