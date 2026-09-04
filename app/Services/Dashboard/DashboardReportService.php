<?php

namespace App\Services\Dashboard;

use App\Enums\BusinessStatus;
use App\Enums\HealthStatus;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\SalesReport;
use App\Models\Team;
use App\Models\User;
use App\Services\Team\TeamService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class DashboardReportService
{
    private const EMPTY_TREND_WEEKS = 12;

    public function __construct(
        private readonly TeamService $teamService,
    ) {}

    public function report(
        User $user,
        ?string $from = null,
        ?string $to = null,
        bool $canViewDashboard = false,
        bool $canSeeClient = false,
        bool $canViewFinance = false,
    ): array {
        $employeeIds = $user->employeeIds();

        $report = match (true) {
            $canViewDashboard => $this->adminReport($from, $to, $canSeeClient, $canViewFinance),
            $employeeIds !== [] => $this->employeeReport($user, $employeeIds, $from, $to, $canSeeClient),
            default => ['heading' => 'My work', 'cards' => []],
        };

        return [
            ...$report,
            // Answered for every branch, not just the employee one: a team
            // leader who also holds `view dashboard` gets the admin report,
            // and the two leader sections have to reach them as well.
            'leads_teams' => $employeeIds !== []
                && Team::ledByEmployees($employeeIds)->exists(),
            'range' => $this->range($from, $to),
        ];
    }

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

    private function adminReport(?string $from, ?string $to, bool $canSeeClient, bool $canViewFinance = false): array
    {
        $trend = $this->trend($from, $to);

        $recentProjects = Project::withLiveClient()
            ->createdBetween($from, $to)
            ->with('client:id,name')
            ->latest()
            ->take(10)
            ->get(['id', 'client_id', 'business_name', 'package_amount', 'amount_paid', 'created_at']);

        return [
            'heading' => 'Dashboard Overview',
            'cards' => [
                [
                    'label' => 'Clients',
                    'value' => (string) Client::where('is_active', true)->createdBetween($from, $to)->count(),
                    'icon' => 'customers',
                    'color' => 'blue',
                ],
                [
                    'label' => 'Projects',
                    'value' => (string) Project::withLiveClient()->createdBetween($from, $to)->count(),
                    'icon' => 'money',
                    'color' => 'amber',
                ],
                [
                    'label' => 'Employees',
                    'value' => (string) Employee::where('is_active', true)->createdBetween($from, $to)->count(),
                    'icon' => 'leads',
                    'color' => 'indigo',
                ],
                [
                    'label' => 'Teams',
                    'value' => (string) Team::where('is_active', true)->createdBetween($from, $to)->count(),
                    'icon' => 'deals',
                    'color' => 'purple',
                ],
            ],
            'recent' => [
                'title' => 'Recent projects',
                'columns' => array_values(array_filter([
                    ['key' => 'business', 'header' => 'Business'],
                    $canSeeClient ? ['key' => 'client', 'header' => 'Client'] : null,
                    ['key' => 'package', 'header' => 'Package'],
                    ['key' => 'paid', 'header' => 'Paid'],
                    ['key' => 'date', 'header' => 'Created'],
                ])),
                'rows' => $recentProjects->map(fn (Project $project) => [
                    'id' => $project->id,
                    'href' => '/admin/projects/'.$project->id,
                    'business' => $project->business_name,
                    ...($canSeeClient ? ['client' => $project->client?->name ?? '—'] : []),
                    'package' => $this->money($project->package_amount),
                    'paid' => $this->money($project->amount_paid),
                    'date' => $project->created_at?->format('M j') ?? '—',
                ])->all(),
            ],
            'finance' => $canViewFinance ? $this->finance($from, $to, $trend) : null,
            'top_projects' => $this->topProjects($from, $to),
            'distributions' => $this->distributions($from, $to),
            'trend' => $trend,
        ];
    }

    public function clientReport(Client $client, ?string $from = null, ?string $to = null): array
    {
        $projects = $client->projects()->createdBetween($from, $to);

        return [
            'range' => $this->range($from, $to),
            'heading' => 'My account — '.$client->name,
            'cards' => [
                [
                    'label' => 'My Projects',
                    'value' => (string) (clone $projects)->count(),
                    'icon' => 'deals',
                    'color' => 'blue',
                ],
                [
                    'label' => 'Package Total',
                    'value' => $this->money((clone $projects)->sum('package_amount')),
                    'icon' => 'money',
                    'color' => 'indigo',
                ],
                [
                    'label' => 'Paid',
                    'value' => $this->money((clone $projects)->sum('amount_paid')),
                    'icon' => 'check',
                    'color' => 'green',
                ],
                [
                    'label' => 'Due',
                    'value' => $this->money((clone $projects)->sum('amount_due')),
                    'icon' => 'invoice',
                    'color' => 'amber',
                ],
            ],
        ];
    }

    private function employeeReport(User $user, array $employeeIds, ?string $from, ?string $to, bool $canSeeClient): array
    {
        $projects = Project::withLiveClient()
            ->whereIn('assigned_employee_id', $employeeIds)
            ->createdBetween($from, $to);

        $ledTeams = $this->teamService->ledTeamsForEmployeeIds($employeeIds, $from, $to);

        $unassignedCount = $ledTeams->sum(
            fn (Team $team) => $team->projects->whereNull('assigned_employee_id')->count(),
        );

        $cards = [
            [
                'label' => 'Assigned Projects',
                'value' => (string) (clone $projects)->count(),
                'icon' => 'deals',
                'color' => 'blue',
            ],
            [
                'label' => 'Sales Target',
                'value' => $this->money((clone $projects)->sum('sales_target')),
                'icon' => 'money',
                'color' => 'indigo',
            ],
            [
                'label' => 'Achieved Sales',
                'value' => $this->money($this->achievedSalesFor((clone $projects), $from, $to)),
                'icon' => 'check',
                'color' => 'green',
            ],
            [
                'label' => 'Amount Due',
                'value' => $this->money((clone $projects)->sum('amount_due')),
                'icon' => 'invoice',
                'color' => 'amber',
            ],
        ];

        if ($ledTeams->isNotEmpty()) {
            $cards[] = [
                'label' => 'Team Members',
                'value' => (string) $ledTeams->sum(
                    fn (Team $team) => $team->members->count(),
                ),
                'icon' => 'leads',
                'color' => 'purple',
            ];
            $cards[] = [
                'label' => 'Unassigned Projects',
                'value' => (string) $unassignedCount,
                'icon' => 'tickets',
                'color' => 'red',
            ];
        }

        return [
            'heading' => 'My work — '.$user->name,
            'cards' => $cards,
            'teams' => $ledTeams->isEmpty()
                ? []
                : $this->teamLeaderTeams($ledTeams, $canSeeClient),
        ];
    }

    private function teamLeaderTeams($teams, bool $canSeeClient): array
    {
        return $teams->map(function (Team $team) use ($canSeeClient) {
            return [
                'team' => $team->only(['id', 'name', 'description']),
                'members' => $team->members
                    ->map(fn (Employee $member) => [
                        'id' => $member->id,
                        'name' => $member->name,
                        'designation' => $member->designation?->name,
                        'email' => $member->email,
                        'phone' => $member->phone,
                        'image_url' => $member->user?->image_url,
                        'thumbnail_url' => $member->user?->thumbnail_url,
                        'role' => $member->pivot->role,
                        'projects_count' => $member->teamProjects->count(),
                        'projects' => $this->projectRows($member->teamProjects, $canSeeClient),
                    ])->values()->all(),
                'unassigned_projects' => $this->projectRows(
                    $team->projects->whereNull('assigned_employee_id'),
                    $canSeeClient,
                ),
            ];
        })->values()->all();
    }

    private function projectRows($projects, bool $canSeeClient): array
    {
        return $projects->map(fn (Project $project) => [
            'id' => $project->id,
            'href' => '/admin/projects/'.$project->id,
            'project' => $project->project_name,
            ...($canSeeClient ? ['client' => $project->client?->name ?? '—'] : []),
            'member' => $project->assignedEmployee?->name ?? '—',
            'package' => $this->money($project->package_amount),
            'due' => $this->money($project->amount_due),
            'status' => $project->business_status?->label() ?? '—',
            'health' => $project->health_status?->label() ?? '—',
            'ends' => $project->end_date?->format('M j, Y') ?? '—',
        ])->values()->all();
    }

    private function achievedSalesFor(Builder $projects, ?string $from, ?string $to): float
    {
        return (float) $this->salesInRange($from, $to)
            ->whereIn('project_id', $projects->toBase()->select('projects.id'))
            ->sum('total_sales');
    }

    private function salesInRange(?string $from, ?string $to): Builder
    {
        return SalesReport::query()
            ->withLiveClient()
            ->overlappingPeriod($from, $to);
    }

    private function trend(?string $from, ?string $to): array
    {
        $rows = $this->salesInRange($from, $to)
            ->toBase()
            ->select('week_start', 'week_end', 'total_sales', 'total_amount_spent', 'total_order_quantity')
            ->orderBy('week_start')
            ->get();

        [$start, $end] = $this->trendWindow($from, $to, $rows);

        $days = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $days[$day->toDateString()] = [
                'date' => $day->toDateString(),
                'sales' => 0.0,
                'spend' => 0.0,
                'orders' => 0.0,
            ];
        }

        foreach ($rows as $row) {
            $weekStart = Carbon::parse($row->week_start);
            $weekEnd = Carbon::parse($row->week_end);

            if ($weekEnd->lt($weekStart)) {
                $weekEnd = $weekStart->copy();
            }

            $length = $weekStart->diffInDays($weekEnd) + 1;

            for ($day = $weekStart->copy(); $day->lte($weekEnd); $day->addDay()) {
                $key = $day->toDateString();

                if (! isset($days[$key])) {
                    continue;
                }

                $days[$key]['sales'] += (float) $row->total_sales / $length;
                $days[$key]['spend'] += (float) $row->total_amount_spent / $length;
                $days[$key]['orders'] += (float) $row->total_order_quantity / $length;
            }
        }

        return array_values($days);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array{0: Carbon, 1: Carbon}
     */
    private function trendWindow(?string $from, ?string $to, $rows): array
    {
        if ($from !== null && $to !== null) {
            $start = Carbon::parse($from);
            $end = Carbon::parse($to);

            return [$start, $end->lt($start) ? $start->copy() : $end];
        }

        if ($rows->isEmpty()) {
            return [
                Carbon::now()->startOfWeek()->subWeeks(self::EMPTY_TREND_WEEKS - 1),
                Carbon::now(),
            ];
        }

        $starts = $rows->map(fn (object $row) => (string) $row->week_start)->sort()->values();
        $ends = $rows->map(fn (object $row) => (string) $row->week_end)->sort()->values();

        return [
            Carbon::parse($from ?? $starts->first()),
            Carbon::parse($to ?? $ends->last()),
        ];
    }

    /**
     * Money for the projects in scope. Every figure follows the date filter, so
     * these move with the four count cards above them rather than always
     * reporting the whole CRM.
     *
     * @param  array<int, array<string, mixed>>  $trend
     */
    private function finance(?string $from, ?string $to, array $trend): array
    {
        $totals = Project::withLiveClient()
            ->createdBetween($from, $to)
            ->toBase()
            ->selectRaw('COALESCE(SUM(package_amount), 0) AS package')
            ->selectRaw('COALESCE(SUM(amount_paid), 0) AS paid')
            ->selectRaw('COALESCE(SUM(amount_due), 0) AS due')
            ->first();

        $sales = (float) array_sum(array_column($trend, 'sales'));

        return [
            // Titles stay fixed — the range label above the cards already says
            // which period is on screen, and a heading that renames itself on
            // every filter change is harder to read, not easier.
            $this->financeCard('Project Value', (float) ($totals->package ?? 0), 'money', 'indigo'),
            $this->financeCard('Amount Paid', (float) ($totals->paid ?? 0), 'check', 'green'),
            $this->financeCard('Amount Due', (float) ($totals->due ?? 0), 'invoice', 'amber'),
            $this->financeCard('Total Sales', $sales, 'deals', 'blue'),
        ];
    }

    /** @return array<string, mixed> */
    private function financeCard(string $label, float $amount, string $icon, string $color): array
    {
        return [
            'label' => $label,
            'value' => $this->money($amount),
            'raw' => $amount,
            'icon' => $icon,
            'color' => $color,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function topProjects(?string $from, ?string $to): array
    {
        [$monthStart, $monthEnd] = Project::currentMonthRange();

        $ranked = Project::withLiveClient()
            ->createdBetween($from, $to)
            ->with('client:id,name')
            ->withSum(
                ['salesReports as month_sales' => fn (Builder $query) => $query->inPeriod($monthStart, $monthEnd)],
                'total_sales',
            )
            ->get(['id', 'client_id', 'business_name', 'project_name', 'sales_target', 'target_months', 'contract_months'])
            ->map(function (Project $project) {
                $target = $project->monthlyTarget();
                $achieved = (float) ($project->month_sales ?? 0);

                return [
                    'id' => $project->id,
                    'href' => '/admin/projects/'.$project->id,
                    'name' => $project->project_name ?? $project->business_name,
                    'client' => $project->client?->name,
                    'achieved' => $this->money($achieved),
                    'target' => $this->money($target),
                    'percent' => $target > 0.0 ? (int) round($achieved / $target * 100) : 0,
                    'has_target' => $target > 0.0,
                ];
            })
            ->filter(fn (array $row) => $row['has_target'])
            ->sortBy('percent')
            ->values();

        return [
            'risk' => $ranked->take(5)->values()->all(),
            'performing' => $ranked->reverse()->take(5)->values()->all(),
        ];
    }

    private function distributions(?string $from, ?string $to): array
    {
        [$monthStart, $monthEnd] = Project::currentMonthRange();

        $health = Project::withLiveClient()
            ->createdBetween($from, $to)
            ->withSum(
                ['salesReports as month_sales' => fn (Builder $query) => $query->inPeriod($monthStart, $monthEnd)],
                'total_sales',
            )
            ->get(['id', 'sales_target', 'target_months', 'contract_months'])
            ->countBy(fn (Project $project) => Project::monthlyHealthStatus(
                (float) ($project->month_sales ?? 0),
                $project->monthlyTarget(),
            )->value);

        $business = Project::withLiveClient()
            ->createdBetween($from, $to)
            ->toBase()
            ->selectRaw('business_status, COUNT(*) AS total')
            ->groupBy('business_status')
            ->pluck('total', 'business_status');

        return [
            'health' => array_map(fn (HealthStatus $case) => [
                'key' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
                'count' => (int) ($health[$case->value] ?? 0),
            ], HealthStatus::cases()),
            'business_status' => array_map(fn (array $option) => [
                'key' => $option['value'],
                'label' => $option['label'],
                'color' => $option['color'],
                'count' => (int) ($business[$option['value']] ?? 0),
            ], BusinessStatus::options()),
        ];
    }

    private function money(mixed $amount): string
    {
        return formatMoney($amount);
    }
}
