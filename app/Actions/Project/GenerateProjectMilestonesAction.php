<?php

namespace App\Actions\Project;

use App\Enums\HealthStatus;
use App\Models\Project;
use App\Models\ProjectMilestone;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class GenerateProjectMilestonesAction
{
    private const STEP_MONTHS = 1;

    public function __construct(
        private readonly RecalculateProjectPerformanceAction $recalculate,
    ) {}

    public function execute(Project $project, bool $regenerate = false): void
    {
        DB::transaction(function () use ($project, $regenerate): void {
            if ($regenerate) {
                $project->milestones()->delete();
            } elseif ($project->milestones()->exists()) {
                return;
            }

            $periods = $this->buildPeriods($project);

            if ($periods === []) {
                return;
            }

            $targets = $this->splitTarget((float) $project->sales_target, count($periods));

            foreach ($periods as $i => $period) {
                $sequence = $i + 1;

                ProjectMilestone::create([
                    'company_id' => $project->company_id,
                    'project_id' => $project->id,
                    'sequence' => $sequence,
                    'label' => "Month {$sequence}",
                    'period_start' => $period['start']->toDateString(),
                    'period_end' => $period['end']->toDateString(),
                    'target_amount' => $targets[$i],
                    'achieved_amount' => 0,
                    'status' => HealthStatus::Upcoming,
                ]);
            }
        });

        $this->recalculate->execute($project->refresh());
    }

    /**
     * @return array<int, array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function buildPeriods(Project $project): array
    {
        if ($project->target_start_date === null || $project->target_deadline === null) {
            return [];
        }

        $step = self::STEP_MONTHS;
        $start = CarbonImmutable::parse($project->target_start_date);
        $deadline = CarbonImmutable::parse($project->target_deadline);

        if ($deadline->lessThanOrEqualTo($start)) {
            return [];
        }

        $periods = [];
        $cursor = $start;

        while ($cursor->lessThan($deadline)) {
            $next = $cursor->addMonths($step);
            $end = $next->greaterThan($deadline) ? $deadline : $next->subDay();

            $periods[] = ['start' => $cursor, 'end' => $end];
            $cursor = $next;
        }

        return $periods;
    }

    /**
     * @return array<int, float>
     */
    private function splitTarget(float $total, int $count): array
    {
        $per = round($total / $count, 2);
        $parts = array_fill(0, $count, $per);
        $parts[$count - 1] = round($total - ($per * ($count - 1)), 2);

        return $parts;
    }
}
