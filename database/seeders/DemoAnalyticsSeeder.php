<?php

namespace Database\Seeders;

use App\Models\MaterialDownload;
use App\Models\StudyMaterial;
use App\Models\VisitorSession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample visitor sessions and download events so the Reports page has live
 * numbers on a fresh local install. The per-file and per-material download
 * counters are set from the generated events, so the totals and the
 * unique-people numbers stay coherent.
 *
 * Demo data only: refuses to run in production, and skips entirely once any
 * real (or previously seeded) analytics rows exist.
 */
class DemoAnalyticsSeeder extends Seeder
{
    private const int VISITORS = 40;

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoAnalyticsSeeder is demo data only — skipped in production.');

            return;
        }

        if (VisitorSession::exists() || MaterialDownload::exists()) {
            $this->command?->info('Analytics rows already exist — demo analytics skipped.');

            return;
        }

        $visitorIds = $this->seedVisitorSessions();
        $events = $this->seedDownloadEvents($visitorIds);

        $this->command?->info(sprintf(
            'Demo analytics in place (%d visitor sessions, %d download events).',
            count($visitorIds),
            $events,
        ));
    }

    /**
     * @return array<int, string>
     */
    private function seedVisitorSessions(): array
    {
        $paths = [
            '/bn', '/bn/browse', '/bn/suggestions', '/bn/books', '/bn/notices',
            '/bn/programs/nu-llb-Pass', '/bn/programs/bar-council', '/en/browse',
        ];

        $visitorIds = [];

        foreach (range(1, self::VISITORS) as $index) {
            $visitorId = Str::lower(Str::random(40));
            $visitorIds[] = $visitorId;

            [$firstSeen, $lastSeen] = match (true) {
                $index <= 6 => [now()->subMinutes(random_int(10, 90)), now()->subMinutes(random_int(0, 4))],
                $index <= 20 => [now()->subHours(random_int(2, 20)), now()->subHours(random_int(1, 12))],
                default => [now()->subDays(random_int(3, 30)), now()->subDays(random_int(1, 25))],
            };

            VisitorSession::create([
                'visitor_id' => $visitorId,
                'first_seen_at' => $firstSeen,
                'last_seen_at' => $lastSeen,
                'last_path' => $paths[array_rand($paths)],
                'page_views' => random_int(1, 25),
            ]);
        }

        return $visitorIds;
    }

    /**
     * @param  array<int, string>  $visitorIds
     */
    private function seedDownloadEvents(array $visitorIds): int
    {
        $total = 0;

        $materials = StudyMaterial::query()
            ->publiclyVisible()
            ->with('files')
            ->get();

        foreach ($materials as $material) {
            $materialTotal = 0;

            foreach ($material->files as $file) {
                $downloads = random_int(4, 60);
                $rows = [];

                foreach (range(1, $downloads) as $i) {
                    $visitorId = random_int(1, 10) === 1
                        ? null
                        : $visitorIds[array_rand($visitorIds)];

                    $rows[] = [
                        'study_material_id' => $material->id,
                        'material_file_id' => $file->id,
                        'visitor_id' => $visitorId,
                        'ip_hash' => hash('sha256', 'demo-ip-'.random_int(1, 25)),
                        'downloaded_at' => now()
                            ->subDays(random_int(0, 29))
                            ->subMinutes(random_int(0, 1439)),
                    ];
                }

                MaterialDownload::insert($rows);

                $file->forceFill(['download_count' => $downloads])->save();

                $materialTotal += $downloads;
                $total += $downloads;
            }

            $material->forceFill([
                'download_count' => $materialTotal,
                'view_count' => $materialTotal * 2 + random_int(10, 120),
            ])->save();
        }

        return $total;
    }
}
