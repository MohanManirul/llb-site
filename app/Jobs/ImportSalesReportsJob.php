<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\Project\SalesReportImportProgress;
use App\Services\Project\SalesReportImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportSalesReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public int $projectId,
        public array $rows,
        public array $columns,
        public string $importId,
    ) {}

    public function handle(
        SalesReportImportService $service,
        SalesReportImportProgress $progress,
    ): void {
        $project = Project::find($this->projectId);

        if ($project === null) {
            $progress->failed($this->importId, 'The project no longer exists.');

            return;
        }

        if ($this->rows === [] || $this->columns === []) {
            $progress->finished($this->importId, ['imported' => 0, 'skipped' => 0, 'details' => []]);

            return;
        }

        $progress->processing($this->importId);

        $progress->finished(
            $this->importId,
            $service->importRows($project, $this->rows, $this->columns),
        );
    }

    public function failed(?Throwable $e): void
    {
        app(SalesReportImportProgress::class)->failed(
            $this->importId,
            'The report CSV could not be imported.',
        );

        Log::error('Sales report CSV import failed.', [
            'project_id' => $this->projectId,
            'import_id' => $this->importId,
            'error' => $e?->getMessage(),
        ]);
    }
}
