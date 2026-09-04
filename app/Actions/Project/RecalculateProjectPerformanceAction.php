<?php

namespace App\Actions\Project;

use App\Enums\HealthStatus;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSale;
use App\Models\SalesReport;
use App\Services\Project\MilestoneProgressService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class RecalculateProjectPerformanceAction
{
    public function __construct(
        private readonly MilestoneProgressService $progress,
    ) {}

    public function execute(Project $project): void
    {
        DB::transaction(function () use ($project): void {
            $today = today();
            $milestones = $project->milestones()->get();

            $this->attributeSales($project, $milestones);

            $reports = $project->salesReports()->get(['id', 'project_id', 'week_start', 'total_sales']);
            $currentId = $this->progress->pickCurrent($milestones, $today)?->id;
            $health = HealthStatus::Upcoming;

            foreach ($milestones as $milestone) {
                $result = $this->progress->progress(
                    [
                        'start' => CarbonImmutable::parse($milestone->period_start),
                        'end' => CarbonImmutable::parse($milestone->period_end),
                        'target' => (float) $milestone->target_amount,
                    ],
                    $this->achievedIn($reports, $milestone),
                    $today,
                );

                $milestone->update([
                    'achieved_amount' => $result->achieved,
                    'status' => $result->health,
                    'evaluated_at' => now(),
                ]);

                if ($milestone->id === $currentId) {
                    $health = $result->health;
                }
            }

            $project->update([
                'achieved_sales' => (float) $reports->sum('total_sales'),
                'health_status' => $health,
                'health_evaluated_at' => now(),
            ]);
        });
    }

    /**
     * @param  Collection<int, SalesReport>  $reports
     */
    private function achievedIn(Collection $reports, ProjectMilestone $milestone): float
    {
        return (float) $reports
            ->filter(fn (SalesReport $report): bool => $report->week_start->gte($milestone->period_start)
                && $report->week_start->lte($milestone->period_end))
            ->sum('total_sales');
    }

    /**
     * @param  Collection<int, ProjectMilestone>  $milestones
     */
    private function attributeSales(Project $project, Collection $milestones): void
    {
        $project->sales()->get()->each(function (ProjectSale $sale) use ($milestones): void {
            $milestone = $milestones->first(
                fn (ProjectMilestone $m): bool => $sale->sale_date->gte($m->period_start) && $sale->sale_date->lte($m->period_end)
            );
            $targetId = $milestone?->id;

            if ($sale->project_milestone_id !== $targetId) {
                $sale->update(['project_milestone_id' => $targetId]);
            }
        });
    }
}
