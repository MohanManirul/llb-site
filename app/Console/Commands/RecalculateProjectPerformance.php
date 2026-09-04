<?php

namespace App\Console\Commands;

use App\Actions\Project\RecalculateProjectPerformanceAction;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class RecalculateProjectPerformance extends Command
{
    protected $signature = 'projects:recalculate-performance';

    protected $description = 'Rebuild every project\'s milestone totals and health status from its sales reports';

    public function handle(RecalculateProjectPerformanceAction $recalculate): int
    {
        $count = 0;

        Project::query()->chunkById(100, function (Collection $projects) use ($recalculate, &$count): void {
            foreach ($projects as $project) {
                $recalculate->execute($project);
                $count++;
            }
        });

        $this->info("Recalculated {$count} projects.");

        return self::SUCCESS;
    }
}
