<?php

namespace App\Services\Dashboard;

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
        return [
            'heading' => 'Dashboard Overview',
            'cards' => [
                [
                    'label' => 'Users',
                    'value' => (string) User::query()->createdBetween($from, $to)->count(),
                    'icon' => 'customers',
                    'color' => 'blue',
                ],
            ],
        ];
    }
}
