<?php

namespace App\Http\Requests\V1\Admin\Teams;

use App\Enums\TeamRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],

            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')
                    ->where('company_id', $this->integer('company_id')),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teams', 'name')
                    ->where('company_id', $this->integer('company_id'))
                    ->where('department_id', $this->integer('department_id')),
            ],

            'description' => ['nullable', 'string', 'max:1000'],

            'is_active' => ['required', 'boolean'],

            'members' => ['required', 'array', 'min:1'],
            'members.*.employee_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('employees', 'id')
                    ->where('company_id', $this->integer('company_id'))
                    ->where('department_id', $this->integer('department_id')),
            ],
            'members.*.role' => ['required', Rule::enum(TeamRole::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $leaderCount = collect($this->input('members', []))
                ->where('role', TeamRole::Leader->value)
                ->count();

            if ($leaderCount === 0) {
                $validator->errors()->add('members', 'A team must have one leader.');
            }

            if ($leaderCount > 1) {
                $validator->errors()->add('members', 'A team can have only one leader.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'department_id.exists' => 'The selected department does not belong to this company.',
            'members.*.employee_id.exists' => 'One or more employees do not belong to this company/department.',
            'name.unique' => 'A team with this name already exists in this department.',
        ];
    }
}
