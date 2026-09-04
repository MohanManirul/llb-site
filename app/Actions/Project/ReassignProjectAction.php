<?php

namespace App\Actions\Project;

use App\Enums\AssignmentReason;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ReassignProjectAction
{
    public function execute(
        Project $project,
        Team $team,
        Employee $employee,
        AssignmentReason $reason = AssignmentReason::Manual,
    ): void {
        $this->guard($project, $team, $employee);

        DB::transaction(function () use ($project, $team, $employee, $reason): void {
            $project->assignments()->whereNull('unassigned_at')->update([
                'unassigned_at' => now(),
            ]);

            $project->update([
                'team_id' => $team->id,
                'department_id' => $team->department_id,
                'assigned_employee_id' => $employee->id,
            ]);

            ProjectAssignment::create([
                'company_id' => $project->company_id,
                'project_id' => $project->id,
                'team_id' => $team->id,
                'employee_id' => $employee->id,
                'assigned_at' => now(),
                'unassigned_at' => null,
                'reason' => $reason,
            ]);
        });
    }

    private function guard(Project $project, Team $team, Employee $employee): void
    {
        if ($team->company_id !== $project->company_id) {
            throw new InvalidArgumentException(
                "Team {$team->id} onno company er — project {$project->id} e assign kora jabe na."
            );
        }

        $isMember = $team->members()
            ->where('employees.id', $employee->id)
            ->exists();

        if (! $isMember) {
            throw new InvalidArgumentException(
                "Employee {$employee->id} team {$team->id} er member noy — assign kora jabe na."
            );
        }
    }
}
