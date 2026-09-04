<?php

namespace App\Observers;

use App\Actions\Project\RecalculateProjectPerformanceAction;
use App\Models\Project;
use App\Models\ProjectSale;

final class ProjectSaleObserver
{
    public function __construct(
        private readonly RecalculateProjectPerformanceAction $recalculate,
    ) {}

    public function creating(ProjectSale $sale): void
    {
        $project = $sale->project;

        if ($project === null) {
            return;
        }

        $sale->company_id ??= $project->company_id;
        $sale->team_id ??= $project->team_id;
        $sale->employee_id ??= $project->assigned_employee_id;
    }

    public function created(ProjectSale $sale): void
    {
        $this->recalculate->execute($sale->project);
    }

    public function updated(ProjectSale $sale): void
    {
        $this->recalculate->execute($sale->project);

        if ($sale->wasChanged('project_id')) {
            $previous = Project::find($sale->getOriginal('project_id'));

            if ($previous !== null) {
                $this->recalculate->execute($previous);
            }
        }
    }

    public function deleted(ProjectSale $sale): void
    {
        $this->recalculate->execute($sale->project);
    }
}
