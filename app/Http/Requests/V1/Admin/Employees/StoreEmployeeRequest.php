<?php

namespace App\Http\Requests\V1\Admin\Employees;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
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
        $companyId = $this->integer('company_id');

        return [
            'user_id' => [
                'required', 'integer', 'exists:users,id',
                Rule::unique('employees', 'user_id')
                    ->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'department_id' => [
                'required', 'integer',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'designation_id' => ['required', 'integer', 'exists:designations,id'],
            'description' => ['nullable', 'string'],
            'joining_date' => ['nullable', 'date'],
            'resignation_date' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.unique' => 'This user is already an employee of this company.',
            'department_id.exists' => 'The selected department does not belong to this company.',
        ];
    }
}
