<?php

namespace App\Observers;

use App\Actions\Project\GenerateProjectMilestonesAction;
use App\Enums\AssignmentReason;
use App\Models\Project;
use App\Models\ProjectAssignment;

final class ProjectObserver
{
    public function __construct(
        private readonly GenerateProjectMilestonesAction $generateMilestones,
    ) {}

    public function created(Project $project): void
    {
        $this->generateMilestones->execute($project);

        if ($project->assigned_employee_id !== null) {
            ProjectAssignment::create([
                'company_id' => $project->company_id,
                'project_id' => $project->id,
                'team_id' => $project->team_id,
                'employee_id' => $project->assigned_employee_id,
                'assigned_at' => $project->created_at ?? now(),
                'unassigned_at' => null,
                'reason' => AssignmentReason::Initial,
            ]);
        }
    }

    public function updated(Project $project): void
    {
        if ($project->wasChanged(['project_type', 'target_start_date', 'target_deadline', 'sales_target'])) {
            $this->generateMilestones->execute($project, regenerate: true);
        }
    }
}
