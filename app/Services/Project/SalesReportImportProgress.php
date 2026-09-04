<?php

namespace App\Services\Project;

use Illuminate\Support\Facades\Cache;

final class SalesReportImportProgress
{
    private const TTL = 3600;

    private const PREFIX = 'sales-report-import:';

    public function queued(string $importId, int $projectId, int $total): void
    {
        $this->put($importId, [
            'import_id' => $importId,
            'project_id' => $projectId,
            'status' => 'queued',
            'total' => $total,
            'imported' => 0,
            'skipped' => 0,
            'details' => [],
            'message' => null,
        ]);
    }

    public function processing(string $importId): void
    {
        $state = $this->find($importId);

        if ($state === null) {
            return;
        }

        $this->put($importId, [...$state, 'status' => 'processing']);
    }

    /**
     * @param  array{imported: int, skipped: int, details: array<int, array<string, mixed>>}  $result
     */
    public function finished(string $importId, array $result): void
    {
        $state = $this->find($importId);

        if ($state === null) {
            return;
        }

        $this->put($importId, [
            ...$state,
            'status' => 'finished',
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
            'details' => $result['details'],
            'message' => sprintf(
                '%d weekly report(s) imported, %d row(s) skipped.',
                $result['imported'],
                $result['skipped'],
            ),
        ]);
    }

    public function failed(string $importId, string $message): void
    {
        $state = $this->find($importId);

        if ($state === null) {
            return;
        }

        $this->put($importId, [
            ...$state,
            'status' => 'failed',
            'message' => $message,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $importId): ?array
    {
        return Cache::get(self::PREFIX.$importId);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function put(string $importId, array $state): void
    {
        Cache::put(self::PREFIX.$importId, $state, self::TTL);
    }
}
