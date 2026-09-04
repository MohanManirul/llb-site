<?php

namespace App\Services\Analytics;

use App\DTOs\FilterData;
use App\Models\MaterialDownload;
use App\Models\MaterialFile;
use App\Models\StudyMaterial;
use App\Models\VisitorSession;
use Illuminate\Contracts\Pagination\Paginator;

final class AnalyticsReportService
{
    private const int ONLINE_WINDOW_MINUTES = 5;

    /**
     * @return array<string, mixed>
     */
    public function live(): array
    {
        $threshold = now()->subMinutes(self::ONLINE_WINDOW_MINUTES);

        $active = VisitorSession::query()
            ->where('last_seen_at', '>=', $threshold)
            ->orderByDesc('last_seen_at')
            ->limit(500)
            ->get(['last_path']);

        $topPages = $active
            ->pluck('last_path')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->map(fn (int $count, string $path) => ['path' => $path, 'visitors' => $count])
            ->values();

        return [
            'online_now' => $active->count(),
            'window_minutes' => self::ONLINE_WINDOW_MINUTES,
            'visitors_today' => VisitorSession::where('last_seen_at', '>=', now()->startOfDay())->count(),
            'downloads_today' => MaterialDownload::where('downloaded_at', '>=', now()->startOfDay())->count(),
            'top_pages' => $topPages,
        ];
    }

    /**
     * The per-material download report: total downloads, unique people and the
     * last download, filterable by date range and type.
     *
     * @return Paginator<int, StudyMaterial>
     */
    public function downloads(FilterData $filters): Paginator
    {
        $from = $filters->filter('date_from');
        $to = $filters->filter('date_to');

        $range = fn ($query) => $query
            ->when($from, fn ($q) => $q->where('downloaded_at', '>=', $from.' 00:00:00'))
            ->when($to, fn ($q) => $q->where('downloaded_at', '<=', $to.' 23:59:59'));

        $page = StudyMaterial::query()
            ->with(['subject:id,name_bn,name_en'])
            ->withCount(['downloadEvents as period_downloads' => $range])
            ->filterable($filters->only(['type']))
            ->searchable($filters->search, ['title_bn', 'title_en'])
            ->orderByDesc($filters->sortBy === 'period_downloads' ? 'period_downloads' : $filters->sortBy)
            ->simplePaginate($filters->perPage);

        $page->getCollection()->transform(function (StudyMaterial $material) use ($range) {
            $events = $range(MaterialDownload::where('study_material_id', $material->id));

            $material->setAttribute(
                'unique_visitors',
                (clone $events)->whereNotNull('visitor_id')->distinct()->count('visitor_id'),
            );
            $material->setAttribute(
                'last_downloaded_at',
                (clone $events)->max('downloaded_at'),
            );

            return $material;
        });

        return $page;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function filesFor(StudyMaterial $material): array
    {
        return $material->files()
            ->get()
            ->map(fn (MaterialFile $file) => [
                'id' => $file->id,
                'label' => $file->label_bn ?? $file->original_name,
                'download_count' => $file->download_count,
                'unique_visitors' => MaterialDownload::where('material_file_id', $file->id)
                    ->whereNotNull('visitor_id')
                    ->distinct()
                    ->count('visitor_id'),
            ])
            ->all();
    }

    public function record(StudyMaterial $material, MaterialFile $file, ?string $visitorId, ?string $ipHash): void
    {
        MaterialDownload::create([
            'study_material_id' => $material->id,
            'material_file_id' => $file->id,
            'visitor_id' => $visitorId,
            'ip_hash' => $ipHash,
            'downloaded_at' => now(),
        ]);
    }
}
