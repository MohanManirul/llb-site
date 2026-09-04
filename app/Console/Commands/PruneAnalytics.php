<?php

namespace App\Console\Commands;

use App\Models\MaterialDownload;
use App\Models\VisitorSession;
use Illuminate\Console\Command;

class PruneAnalytics extends Command
{
    protected $signature = 'llb:prune-analytics
        {--sessions-days=30 : Delete visitor sessions idle longer than this}
        {--downloads-days=365 : Delete download events older than this}';

    protected $description = 'Prune old visitor sessions and download events; the counters on materials and files keep the lifetime totals.';

    public function handle(): int
    {
        $sessions = VisitorSession::query()
            ->where('last_seen_at', '<', now()->subDays((int) $this->option('sessions-days')))
            ->delete();

        $downloads = MaterialDownload::query()
            ->where('downloaded_at', '<', now()->subDays((int) $this->option('downloads-days')))
            ->delete();

        $this->info("Pruned {$sessions} visitor sessions and {$downloads} download events.");

        return self::SUCCESS;
    }
}
