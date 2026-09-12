<?php

namespace App\Http\Requests\V1\StudentApi;

use App\Support\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => Phone::normalize($this->input('phone'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => [
                'required', 'string', Phone::RULE,
                Rule::unique('students', 'phone')->ignore($this->user('student')?->id),
            ],
            'program_id' => ['nullable', 'integer', Rule::exists('programs', 'id')],
            'college_id' => ['nullable', 'integer', Rule::exists('colleges', 'id')->where('is_active', true)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid mobile number, for example 01712345678.',
            'phone.unique' => 'An account with this mobile number already exists.',
        ];
    }
}
