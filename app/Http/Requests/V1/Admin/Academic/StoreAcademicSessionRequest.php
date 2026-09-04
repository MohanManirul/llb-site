<?php

namespace App\Http\Requests\V1\Admin\Academic;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicSessionRequest extends FormRequest
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
            'label' => [
                'required', 'string', 'max:20', 'regex:/^\d{4}-\d{2}$/',
                Rule::unique('academic_sessions', 'label')
                    ->ignore($this->route('academicSession')?->id),
            ],
            'start_year' => ['required', 'integer', 'between:2000,2100'],
            'end_year' => ['required', 'integer', 'between:2000,2100', 'gte:start_year'],
            'is_current' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
