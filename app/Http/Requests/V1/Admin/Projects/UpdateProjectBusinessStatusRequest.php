<?php

namespace App\Http\Requests\V1\Admin\Projects;

use App\Enums\BusinessStatus;
use App\Models\Project;
use App\Traits\ChecksProjectAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectBusinessStatusRequest extends FormRequest
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
        return [
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
            'business_status.required' => 'A project status is required.',
            'business_status.enum' => 'The selected project status is invalid.',
        ];
    }
}
