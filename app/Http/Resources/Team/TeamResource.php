<?php

namespace App\Http\Resources\Team;

use App\Enums\TeamRole;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    private function getDesignationWithTeamRole(Employee $member): string
    {
        $designation = $member->designation?->name ?? 'N/A';
        $roleLabel = TeamRole::tryFrom($member->pivot->role)?->label() ?? 'N/A';

        return "{$designation} ({$roleLabel})";
    }

    /**
     * @return array<string, mixed>|null
     */
    public function toArray(Request $request): ?array
    {
        if ($this->resource === null) {
            return null;
        }

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company_name' => $this->whenLoaded('company', fn () => $this->company?->name),
            'department_id' => $this->department_id,
            'department_name' => $this->whenLoaded('department', fn () => $this->department?->name),
            'name' => $this->name,
            'description' => $this->description,
            'leader' => $this->when(
                $this->relationLoaded('leaders'),
                fn () => $this->leaders->first() === null ? null : [
                    'id' => $this->leaders->first()->id,
                    'name' => $this->leaders->first()->user?->name,
                ],
            ),
            'members_count' => $this->whenCounted('members'),
            'is_active' => $this->is_active,
            'members' => $this->when(
                $this->relationLoaded('members') || $this->relationLoaded('regularMembers'),
                fn () => $this->relationLoaded('members')
                    ? $this->members->map(fn (Employee $member) => [
                        'id' => $member->id,
                        'name' => $member->user?->name,
                        'designation' => $this->getDesignationWithTeamRole($member),
                        'image_url' => $member->user?->image_url,
                        'thumbnail_url' => $member->user?->thumbnail_url,
                        'role' => $member->pivot->role,
                    ])
                    : $this->regularMembers->map(fn (Employee $employee) => [
                        'value' => $employee->id,
                        'label' => $employee->user?->name,
                    ])->values(),
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
