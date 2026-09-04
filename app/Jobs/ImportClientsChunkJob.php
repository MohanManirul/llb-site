<?php

namespace App\Jobs;

use App\Services\Client\ClientImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportClientsChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $rows;

    public array $columns;

    public function __construct(array $rows, array $columns)
    {
        $this->rows = $rows;
        $this->columns = $columns;
    }

    public function handle(): void
    {
        try {
            collect($this->rows)
                ->chunk(ClientImportService::CHUNK_SIZE)
                ->each(function ($chunk) {
                    dispatch(new ImportClientsJob(
                        $chunk->values()->all(),
                        $this->columns
                    ));
                });
        } catch (\Throwable $e) {
            Log::error('Client CSV import chunk dispatch failed', [
                'rows' => count($this->rows),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
