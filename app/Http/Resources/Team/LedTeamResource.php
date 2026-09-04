<?php

namespace App\Http\Resources\Team;

use App\Http\Resources\Project\ProjectSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class LedTeamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $projects = $this->relationLoaded('projects')
            ? $this->projects
            : new Collection;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'company_id' => $this->company_id,
            'company_name' => $this->whenLoaded('company', fn () => $this->company?->name),
            'department_id' => $this->department_id,
            'department_name' => $this->whenLoaded('department', fn () => $this->department?->name),
            'members_count' => $this->whenLoaded('members', fn () => $this->members->count()),
            'members' => TeamMemberResource::collection($this->whenLoaded('members')),
            'projects_count' => $projects->count(),
            'projects' => ProjectSummaryResource::collection($projects),
            'unassigned_projects' => ProjectSummaryResource::collection(
                $projects->whereNull('assigned_employee_id')->values(),
            ),
        ];
    }
}
