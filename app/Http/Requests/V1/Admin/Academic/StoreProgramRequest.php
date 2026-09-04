<?php

namespace App\Http\Requests\V1\Admin\Academic;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProgramRequest extends FormRequest
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
            'name_bn' => ['required', 'string', 'max:150'],
            'name_en' => ['required', 'string', 'max:150'],
            'short_name_bn' => ['nullable', 'string', 'max:40'],
            'short_name_en' => ['nullable', 'string', 'max:40'],
            'has_levels' => ['required', 'boolean'],
            'level_label_bn' => ['nullable', 'required_if:has_levels,true', 'string', 'max:30'],
            'level_label_en' => ['nullable', 'required_if:has_levels,true', 'string', 'max:30'],
            'has_exam_stages' => ['required', 'boolean'],
            'has_sessions' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
