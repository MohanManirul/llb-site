<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\SalesReport;
use Illuminate\Database\Eloquent\Collection;

final class SalesReportService
{
    /**
     * @return Collection<int, SalesReport>
     */
    public function listForProject(Project $project, ?string $start = null, ?string $end = null): Collection
    {
        return $project->salesReports()
            ->when(
                $start !== null && $end !== null,
                fn ($query) => $query->inPeriod($start, $end),
            )
            ->latestFirst()
            ->get();
    }

    public function createForProject(Project $project, array $data): SalesReport
    {
        return $project->salesReports()->create([
            ...$data,
            'company_id' => $project->company_id,
        ]);
    }

    public function update(SalesReport $salesReport, array $data): SalesReport
    {
        $salesReport->update($data);

        return $salesReport->refresh();
    }

    public function delete(SalesReport $salesReport): void
    {
        $salesReport->delete();
    }
}
