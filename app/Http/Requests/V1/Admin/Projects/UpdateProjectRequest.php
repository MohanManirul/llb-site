<?php

namespace App\Http\Requests\V1\Admin\Projects;

use App\Models\Project;
use App\Traits\ChecksProjectAccess;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends ProjectRequest
{
    use ChecksProjectAccess;

    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');

        $this->ensureCanEditProject($this->user(), $project);

        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'project_name' => [
                'required', 'string', 'max:150',
                Rule::unique('projects', 'project_name')
                    ->ignore($project->id)
                    ->whereNull('deleted_at'),
            ],

            ...$this->commonRules($project->company_id, $this->integer('team_id')),
        ];
    }
}
