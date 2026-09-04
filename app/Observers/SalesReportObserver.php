<?php

namespace App\Observers;

use App\Actions\Project\RecalculateProjectPerformanceAction;
use App\Models\SalesReport;

final class SalesReportObserver
{
    public function __construct(
        private readonly RecalculateProjectPerformanceAction $recalculate,
    ) {}

    public function saved(SalesReport $salesReport): void
    {
        $this->refresh($salesReport);
    }

    public function deleted(SalesReport $salesReport): void
    {
        $this->refresh($salesReport);
    }

    private function refresh(SalesReport $salesReport): void
    {
        $project = $salesReport->project()->first();

        if ($project !== null) {
            $this->recalculate->execute($project);
        }
    }
}
