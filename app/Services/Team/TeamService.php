<?php

namespace App\Services\Team;

use App\DTOs\FilterData;
use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class TeamService
{
    /**
     * @return array<int, array{value: int, label: string}>
     */
    public function searchOptions(string $search = ''): array
    {
        return Team::query()->latest()
            ->when($search !== '', fn ($q) => $q->whereLike('name', "%{$search}%"))
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name'])
            ->map(fn (Team $team) => [
                'value' => $team->id,
                'label' => $team->name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public function memberSearchOptions(?string $role, string $search = ''): array
    {
        return Employee::query()->latest()
            ->whereIn('id', function ($sub) use ($role) {
                $sub->select('tm.employee_id')
                    ->from('team_members as tm')
                    ->join('teams as t', 't.id', '=', 'tm.team_id')
                    ->whereNull('t.deleted_at');

                if (TeamRole::tryFrom((string) $role) !== null) {
                    $sub->where('tm.role', $role);
                }
            })
            ->when($search !== '', fn ($q) => $q->whereHas(
                'user',
                fn ($userQuery) => $userQuery->whereLike('name', "%{$search}%"),
            ))
            ->orderBy(User::query()->select('name')->whereColumn('users.id', 'employees.user_id'))
            ->limit(10)
            ->with('user:id,name')
            ->get(['id', 'user_id'])
            ->map(fn (Employee $employee) => [
                'value' => $employee->id,
                'label' => $employee->user?->name,
            ])
            ->all();
    }

    /**
     * @return Paginator<int, Team>
     */
    public function paginate(FilterData $filters): Paginator
    {
        $role = $filters->filter('role');

        $relation = match ($role) {
            TeamRole::Leader->value => 'leaders',
            TeamRole::Member->value => 'regularMembers',
            default => 'members',
        };

        return Team::query()
            ->select('teams.*')
            ->selectSub(
                Employee::query()
                    ->select('users.name')
                    ->join('team_members as tm', 'tm.employee_id', '=', 'employees.id')
                    ->join('users', 'users.id', '=', 'employees.user_id')
                    ->whereColumn('tm.team_id', 'teams.id')
                    ->where('tm.role', TeamRole::Leader->value)
                    ->limit(1),
                'leader',
            )
            ->selectSub(
                Company::query()
                    ->select('companies.name')
                    ->whereColumn('companies.id', 'teams.company_id')
                    ->limit(1),
                'company_name',
            )
            ->selectSub(
                Department::query()
                    ->select('departments.name')
                    ->whereColumn('departments.id', 'teams.department_id')
                    ->limit(1),
                'department_name',
            )
            ->with([
                'company:id,name',
                'department:id,name',
                'leaders:employees.id,employees.user_id',
                'leaders.user:id,name',
                'regularMembers:employees.id,employees.user_id',
                'regularMembers.user:id,name',
            ])
            ->withCount('members')
            ->searchable($filters->search, ['name', 'company.name', 'department.name', 'leaders.user.name'])
            ->filterable($filters->only([
                'is_active',
                'team_id' => 'id',
                'company_id',
                'department_id',
            ]))
            ->when(
                $role !== null,
                fn (Builder $query) => $query->has($role === TeamRole::Leader->value ? 'leaders' : 'regularMembers'),
            )
            ->when(
                $filters->hasFilter('employee_id'),
                fn (Builder $query) => $query->whereHas(
                    $relation,
                    fn (Builder $memberQuery) => $memberQuery->where('employees.id', (int) $filters->filter('employee_id')),
                ),
            )
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return Collection<int, Team>
     */
    public function ledTeamsFor(Employee $employee, ?string $from = null, ?string $to = null): Collection
    {
        return $this->ledTeamsForEmployeeIds([$employee->id], $from, $to);
    }

    public function ledTeamsForEmployeeIds(array $employeeIds, ?string $from = null, ?string $to = null): Collection
    {
        if ($employeeIds === []) {
            return new Collection;
        }

        $teams = Team::ledByEmployees($employeeIds)
            ->with($this->ledTeamRelations($from, $to))
            ->orderBy('teams.name')
            ->get();

        $teams->each(function (Team $team) {
            $team->members->each(fn (Employee $member) => $member->setRelation(
                'teamProjects',
                $team->projects->where('assigned_employee_id', $member->id)->values(),
            ));
        });

        return $teams;
    }

    public function leadsTeam(Employee $employee, Team $team): bool
    {
        return $this->leadsTeamAsAny([$employee->id], $team);
    }

    public function leadsTeamAsAny(array $employeeIds, Team $team): bool
    {
        if ($employeeIds === []) {
            return false;
        }

        return $team->leaders()
            ->whereIn('employees.id', $employeeIds)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function ledTeamRelations(?string $from = null, ?string $to = null): array
    {
        return [
            'company:id,name',
            'department:id,name',
            'members' => fn ($query) => $query
                ->select('employees.id', 'employees.user_id', 'employees.designation_id')
                ->with(['user:id,name,email,phone,image', 'designation'])
                ->orderByPivot('role'),
            'projects' => fn ($query) => $query
                ->withLiveClient()
                ->createdBetween($from, $to)
                ->with([
                    'client:id,name',
                    'assignedEmployee:id,user_id',
                    'assignedEmployee.user:id,name',
                ])
                ->latest(),
        ];
    }

    public function create(array $data): Team
    {
        return DB::transaction(function () use ($data) {
            $team = Team::create([
                'company_id' => $data['company_id'],
                'department_id' => $data['department_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $team->members()->attach(
                $this->buildMemberPayload($data['members'] ?? [])
            );

            return $team;
        });
    }

    public function update(Team $team, array $data): Team
    {
        return DB::transaction(function () use ($team, $data) {
            $team->update([
                'company_id' => $data['company_id'],
                'department_id' => $data['department_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? $team->is_active,
            ]);

            $team->members()->sync(
                $this->buildMemberPayload($data['members'] ?? [])
            );

            return $team;
        });
    }

    private function buildMemberPayload(array $members): array
    {
        return collect($members)
            ->mapWithKeys(fn (array $member) => [
                $member['employee_id'] => ['role' => TeamRole::from($member['role'])->value],
            ])
            ->all();
    }
}
