<?php

namespace App\Services\Dashboard;

use App\Enums\MaterialType;
use App\Models\Notice;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Carbon;

final class DashboardReportService
{
    /**
     * @return array<string, mixed>
     */
    public function report(
        User $user,
        ?string $from = null,
        ?string $to = null,
        bool $canViewDashboard = false,
    ): array {
        $report = $canViewDashboard
            ? $this->adminReport($from, $to)
            : ['heading' => 'My work', 'cards' => []];

        return [
            ...$report,
            'range' => $this->range($from, $to),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function range(?string $from, ?string $to): array
    {
        $scoped = $from !== null && $to !== null;

        return [
            'from' => $from,
            'to' => $to,
            'scoped' => $scoped,
            'label' => $scoped
                ? Carbon::parse($from)->format('M j, Y').' – '.Carbon::parse($to)->format('M j, Y')
                : 'All time',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminReport(?string $from, ?string $to): array
    {
        $published = StudyMaterial::query()->publiclyVisible();

        return [
            'heading' => 'Dashboard Overview',
            'cards' => [
                [
                    'label' => 'Suggestions',
                    'value' => (string) (clone $published)->where('type', MaterialType::Suggestion)->createdBetween($from, $to)->count(),
                    'icon' => 'suggestions',
                    'color' => 'indigo',
                ],
                [
                    'label' => 'Books',
                    'value' => (string) (clone $published)->where('type', MaterialType::Book)->createdBetween($from, $to)->count(),
                    'icon' => 'books',
                    'color' => 'blue',
                ],
                [
                    'label' => 'Class Notes',
                    'value' => (string) (clone $published)->where('type', MaterialType::Note)->createdBetween($from, $to)->count(),
                    'icon' => 'notes',
                    'color' => 'green',
                ],
                [
                    'label' => 'Total Downloads',
                    'value' => (string) StudyMaterial::query()->sum('download_count'),
                    'icon' => 'downloads',
                    'color' => 'amber',
                ],
                [
                    'label' => 'Active Notices',
                    'value' => (string) Notice::query()->publiclyVisible()->unexpired()->count(),
                    'icon' => 'notices',
                    'color' => 'red',
                ],
                [
                    'label' => 'Subjects',
                    'value' => (string) Subject::query()->where('is_active', true)->count(),
                    'icon' => 'subjects',
                    'color' => 'purple',
                ],
            ],
        ];
    }
}
