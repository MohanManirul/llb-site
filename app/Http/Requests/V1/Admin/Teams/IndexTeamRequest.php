<?php

namespace App\Http\Requests\V1\Admin\Teams;

use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class IndexTeamRequest extends IndexRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'is_active' => ['nullable', 'in:0,1'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'role' => ['nullable', 'in:leader,member'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return [
            'company_name', 'department_name', 'name', 'leader',
            'members_count', 'is_active', 'created_at',
        ];
    }
}
