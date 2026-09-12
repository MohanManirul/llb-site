<?php

namespace App\Http\Requests\V1\StudentApi;

use App\Support\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterStudentRequest extends FormRequest
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
            'phone' => ['required', 'string', Phone::RULE, 'unique:students,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'college_id' => ['required', 'integer', Rule::exists('colleges', 'id')->where('is_active', true)],
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
