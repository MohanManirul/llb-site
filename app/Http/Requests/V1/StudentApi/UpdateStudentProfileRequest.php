<?php

namespace App\Http\Requests\V1\StudentApi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentProfileRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'program_id' => ['nullable', 'integer', Rule::exists('programs', 'id')],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
