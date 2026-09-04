<?php

namespace App\Http\Requests\V1\Admin\Projects;

use Illuminate\Validation\Rule;

class StoreProjectRequest extends ProjectRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $companyId = $this->integer('company_id');
        $teamId = $this->integer('team_id');

        return [
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],

            'project_name' => [
                'required', 'string', 'max:150',
                Rule::unique('projects', 'project_name')->whereNull('deleted_at'),
            ],

            ...$this->commonRules($companyId, $teamId),
        ];
    }
}
