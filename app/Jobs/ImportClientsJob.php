<?php

namespace App\Jobs;

use App\Services\Client\ClientImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportClientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public array $rows;

    public array $columns;

    public function __construct(array $rows, array $columns)
    {
        $this->rows = $rows;
        $this->columns = $columns;
    }

    public function handle(ClientImportService $service): void
    {
        if ($this->rows === [] || $this->columns === []) {
            return;
        }

        $service->importRows($this->rows, $this->columns);
    }
}
