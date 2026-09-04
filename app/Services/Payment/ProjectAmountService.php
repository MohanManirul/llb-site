<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Project;

class ProjectAmountService
{
    /**
     * @return array{paid: float, due: float, total: float}
     */
    public function forProject(int $projectId): array
    {
        return $this->forProjectModel(Project::findOrFail($projectId));
    }

    /**
     * @return array{paid: float, due: float, total: float}
     */
    public function forProjectModel(Project $project): array
    {
        $paid = (float) Payment::where('project_id', $project->id)->sum('amount');
        $total = (float) ($project->total_amount ?? $project->package_amount ?? 0);

        return [
            'paid' => $paid,
            'due' => $total - $paid,
            'total' => $total,
        ];
    }
}
