<?php

namespace App\Http\Resources\Team;

use App\Enums\TeamRole;
use App\Http\Resources\Project\ProjectSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user?->name,
            'designation' => $this->getDesignationWithTeamRole(),
            'email' => $this->user?->email,
            'phone' => $this->user?->phone,
            'image_url' => $this->user?->image_url,
            'thumbnail_url' => $this->user?->thumbnail_url,
            'role' => $this->whenPivotLoaded('team_members', fn () => $this->pivot->role),
            'projects_count' => $this->whenLoaded('teamProjects', fn () => $this->teamProjects->count()),
            'projects' => ProjectSummaryResource::collection($this->whenLoaded('teamProjects')),
        ];
    }

    private function getDesignationWithTeamRole(): string
    {
        $designation = $this->designation?->name ?? 'N/A';

        if (! $this->relationLoaded('pivot') && ! $this->pivot) {
            return $designation;
        }

        $roleLabel = TeamRole::tryFrom($this->pivot?->role ?? '')?->label() ?? 'N/A';

        return "{$designation} ({$roleLabel})";
    }
}
