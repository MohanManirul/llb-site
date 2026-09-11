<?php

namespace App\Http\Requests\V1\TeacherApi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterTeacherRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255', 'unique:teachers,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'college_id' => ['required', 'integer', Rule::exists('colleges', 'id')->where('is_active', true)],
            'designation_bn' => ['nullable', 'string', 'max:100'],
            'designation_en' => ['nullable', 'string', 'max:100'],
        ];
    }
}
