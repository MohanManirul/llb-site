<?php

namespace App\Services\ActivityLog;

use App\DTOs\FilterData;
use App\Models\ActivityLog;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

final class ActivityLogService
{
    /**
     * @return Paginator<int, ActivityLog>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return ActivityLog::query()
            ->with(['causer', 'impersonator'])
            ->searchable($filters->search, ['description', 'type'])
            ->filterable($filters->only(['type', 'subject_type']))
            ->when(
                $filters->hasFilter('date_from'),
                fn (Builder $query) => $query->where(
                    'created_at',
                    '>=',
                    CarbonImmutable::parse($filters->filter('date_from'))->startOfDay(),
                ),
            )
            ->when(
                $filters->hasFilter('date_to'),
                fn (Builder $query) => $query->where(
                    'created_at',
                    '<',
                    CarbonImmutable::parse($filters->filter('date_to'))->addDay()->startOfDay(),
                ),
            )
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return array{types: array<int, array{value: string, label: string}>, subject_types: array<int, array{value: string, label: string}>}
     */
    public function filterOptions(): array
    {
        return [
            'types' => ActivityLog::query()
                ->distinct()
                ->orderBy('type')
                ->pluck('type')
                ->map(fn (string $type) => ['value' => $type, 'label' => $type])
                ->all(),

            'subject_types' => ActivityLog::query()
                ->whereNotNull('subject_type')
                ->distinct()
                ->orderBy('subject_type')
                ->pluck('subject_type')
                ->map(fn (string $subject) => ['value' => $subject, 'label' => class_basename($subject)])
                ->all(),
        ];
    }

    public function delete(ActivityLog $activityLog): void
    {
        $activityLog->delete();
    }
}
