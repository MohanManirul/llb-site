<?php

namespace App\Http\Requests\V1\Admin\Projects;

use App\Enums\BusinessStatus;
use App\Enums\ProjectType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function commonRules(int $companyId, int $teamId): array
    {
        return [
            'team_id' => [
                'required', 'integer',
                Rule::exists('teams', 'id')
                    ->where('company_id', $companyId)
                    ->withoutTrashed(),
            ],

            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],

            'assigned_employee_id' => [
                'nullable', 'integer',
                Rule::exists('team_members', 'employee_id')
                    ->where('team_id', $teamId)
                    ->whereNull('deleted_at'),
            ],

            'business_name' => ['required', 'string', 'max:150'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],

            'start_date' => ['required', 'date'],
            'contract_months' => ['required', 'integer', 'between:1,255'],
            'contract_days' => ['nullable', 'integer', 'between:0,365'],

            'contact_person' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:20'],

            'package_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'total_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'gte:package_amount'],
            'amount_paid' => ['nullable', 'numeric', 'min:0', 'lte:package_amount'],
            'next_payment_date' => ['required', 'date'],

            'project_type' => ['required', Rule::enum(ProjectType::class)],

            'sales_target' => ['nullable', 'required_if:project_type,challenge_based', 'numeric', 'min:0', 'max:999999999999.99'],
            'target_start_date' => ['nullable', 'date'],
            'target_months' => ['nullable', 'required_if:project_type,challenge_based', 'integer', 'between:1,255'],
            'target_days' => ['nullable', 'integer', 'between:0,365'],

            'business_status' => [
                'required', 'string',
                Rule::enum(BusinessStatus::class),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'team_id.exists' => 'The selected team does not belong to this company.',
            'assigned_employee_id.exists' => 'The selected owner is not a member of this team.',
            'amount_paid.lte' => 'Amount paid cannot exceed the package amount.',
            'total_amount.gte' => 'Total amount must be equal to or greater than package amount.',
            'project_name.unique' => 'This project name is already taken.',
            'sales_target.required_if' => 'A sales goal is required for challenge based projects.',
            'target_months.required_if' => 'Target months are required for challenge based projects.',
            'business_status.enum' => 'The selected project status is invalid.',
        ];
    }
}
