<?php

namespace App\Services\Project;

use App\DTOs\MilestoneProgress;
use App\Enums\HealthStatus;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\SalesReport;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class MilestoneProgressService
{
    public function forProject(Project $project): MilestoneProgress
    {
        $this->attachMany(new Collection([$project]));

        return $project->milestoneProgress ?? MilestoneProgress::none();
    }

    /**
     * @param  Collection<int, Project>  $projects
     */
    public function attachMany(Collection $projects): void
    {
        if ($projects->isEmpty()) {
            return;
        }

        $today = today();
        $stored = $this->storedWindows($projects->pluck('id')->all(), $today);

        $windows = [];

        foreach ($projects as $project) {
            $window = $stored[$project->id] ?? $this->derivedWindow($project, $today);

            if ($window !== null) {
                $windows[$project->id] = $window;
            }
        }

        $achieved = $this->achievedByProject($windows);

        foreach ($projects as $project) {
            $window = $windows[$project->id] ?? null;

            $project->milestoneProgress = $window === null
                ? MilestoneProgress::none()
                : $this->progress($window, (float) ($achieved[$project->id] ?? 0), $today);
        }
    }

    /**
     * @param  Collection<int, ProjectMilestone>  $milestones
     */
    public function pickCurrent(Collection $milestones, ?CarbonInterface $today = null): ?ProjectMilestone
    {
        if ($milestones->isEmpty()) {
            return null;
        }

        $today ??= today();
        $ordered = $milestones->sortBy('sequence')->values();

        $covering = $ordered->first(
            fn (ProjectMilestone $milestone): bool => $today->gte($milestone->period_start)
                && $today->lte($milestone->period_end),
        );

        if ($covering !== null) {
            return $covering;
        }

        return $today->lt($ordered->first()->period_start)
            ? $ordered->first()
            : $ordered->last();
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable, target: float}  $window
     */
    public function progress(array $window, float $achieved, ?CarbonInterface $today = null): MilestoneProgress
    {
        $today ??= today();
        $started = $today->greaterThanOrEqualTo($window['start']);
        $target = $window['target'];

        $ratio = $started && $target > 0.0 ? $achieved / $target : 0.0;
        $raw = $ratio * 100;

        return new MilestoneProgress(
            milestoneId: $window['id'] ?? null,
            sequence: $window['sequence'] ?? null,
            label: $window['label'] ?? null,
            periodStart: $window['start']->toDateString(),
            periodEnd: $window['end']->toDateString(),
            target: $target,
            achieved: $started ? $achieved : 0.0,
            progress: min(100.0, $raw),
            rawProgress: $raw,
            started: $started,
            health: $target > 0.0
                ? HealthStatus::fromRatio($ratio, $started)
                : HealthStatus::Upcoming,
        );
    }

    /**
     * @param  array<int, int>  $projectIds
     * @return array<int, array<string, mixed>>
     */
    private function storedWindows(array $projectIds, CarbonInterface $today): array
    {
        $windows = [];

        $grouped = ProjectMilestone::query()
            ->whereIn('project_id', $projectIds)
            ->orderBy('project_id')
            ->orderBy('sequence')
            ->get(['id', 'project_id', 'sequence', 'label', 'period_start', 'period_end', 'target_amount'])
            ->groupBy('project_id');

        foreach ($grouped as $projectId => $milestones) {
            $milestone = $this->pickCurrent($milestones, $today);

            if ($milestone === null) {
                continue;
            }

            $windows[(int) $projectId] = [
                'id' => $milestone->id,
                'sequence' => $milestone->sequence,
                'label' => $milestone->label,
                'start' => CarbonImmutable::parse($milestone->period_start),
                'end' => CarbonImmutable::parse($milestone->period_end),
                'target' => (float) $milestone->target_amount,
            ];
        }

        return $windows;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function derivedWindow(Project $project, CarbonInterface $today): ?array
    {
        $anchor = $project->target_start_date ?? $project->start_date;

        if ($anchor === null) {
            return null;
        }

        $start = CarbonImmutable::parse($anchor);
        $deadlineDate = $project->target_deadline ?? $project->end_date;
        $deadline = $deadlineDate === null ? null : CarbonImmutable::parse($deadlineDate);

        if ($deadline !== null && $deadline->lessThanOrEqualTo($start)) {
            $deadline = null;
        }

        $total = $this->countPeriods($start, $deadline);
        $cursor = $start;
        $sequence = 1;
        $end = $this->periodEnd($cursor, $deadline);

        while ($today->greaterThan($end) && $sequence < $total) {
            $cursor = $cursor->addMonth();
            $sequence++;
            $end = $this->periodEnd($cursor, $deadline);
        }

        return [
            'id' => null,
            'sequence' => $sequence,
            'label' => "Month {$sequence}",
            'start' => $cursor,
            'end' => $end,
            'target' => $total > 0 ? round((float) ($project->sales_target ?? 0) / $total, 2) : 0.0,
        ];
    }

    private function periodEnd(CarbonImmutable $start, ?CarbonImmutable $deadline): CarbonImmutable
    {
        $next = $start->addMonth();

        return $deadline !== null && $next->greaterThan($deadline)
            ? $deadline
            : $next->subDay();
    }

    private function countPeriods(CarbonImmutable $start, ?CarbonImmutable $deadline): int
    {
        if ($deadline === null) {
            return 1;
        }

        $count = 0;
        $cursor = $start;

        while ($cursor->lessThan($deadline)) {
            $cursor = $cursor->addMonth();
            $count++;
        }

        return max(1, $count);
    }

    /**
     * @param  array<int, array<string, mixed>>  $windows
     * @return array<int, float>
     */
    private function achievedByProject(array $windows): array
    {
        if ($windows === []) {
            return [];
        }

        return SalesReport::query()
            ->select('project_id', DB::raw('SUM(total_sales) as total'))
            ->where(function (Builder $query) use ($windows): void {
                foreach ($windows as $projectId => $window) {
                    $query->orWhere(fn (Builder $scoped) => $scoped
                        ->where('project_id', $projectId)
                        ->whereBetween('week_start', [
                            $window['start']->toDateString(),
                            $window['end']->toDateString(),
                        ]));
                }
            })
            ->groupBy('project_id')
            ->pluck('total', 'project_id')
            ->map(fn ($total): float => (float) $total)
            ->all();
    }
}
