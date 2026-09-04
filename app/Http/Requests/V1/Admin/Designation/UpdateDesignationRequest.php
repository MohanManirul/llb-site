<?php

namespace App\Http\Requests\V1\Admin\Designation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDesignationRequest extends FormRequest
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
        $designationId = $this->route('designation')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('designations', 'name')->ignore($designationId),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'This designation name already exists.',
        ];
    }
}
