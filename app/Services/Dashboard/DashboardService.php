<?php

namespace App\Services\Dashboard;

use App\Http\Resources\Team\LedTeamResource;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectSale;
use App\Models\Team;
use App\Models\User;
use App\Services\Team\TeamService;
use App\Traits\ChecksDashboardAccess;
use Illuminate\Database\Eloquent\Collection;

final class DashboardService
{
    use ChecksDashboardAccess;

    public function __construct(
        private readonly TeamService $teamService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'roles' => $user->effectiveRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'sections' => $this->sections($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sections(User $user): array
    {
        $sections = [];

        if ($this->canViewCompanyDashboard($user)) {
            $sections['overview'] = $this->overview();
        }

        if ($user->can('view finance')) {
            $sections['finance'] = $this->finance();
        }

        $employees = $user->employees()
            ->with(['company:id,name', 'department:id,name', 'designation:id,name'])
            ->get();

        if ($employees->isNotEmpty()) {
            $sections['employee'] = $this->employeeSection($user, $employees);

            $ledTeams = $this->teamService->ledTeamsForEmployeeIds($employees->pluck('id')->all());

            if ($ledTeams->isNotEmpty()) {
                $sections['teams'] = $this->teamLeaderSection($ledTeams);
            }
        }

        return $sections;
    }

    /** @return array<string, mixed> */
    private function overview(): array
    {
        return [
            'total_clients' => Client::where('is_active', true)->count(),
            'total_employees' => Employee::where('is_active', true)->count(),
            'total_teams' => Team::where('is_active', true)->count(),
            'total_projects' => Project::withLiveClient()->count(),
            'active_projects' => Project::withLiveClient()->where(function ($query) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', today());
            })->count(),
            'recent_projects' => Project::withLiveClient()
                ->with('client:id,name')
                ->latest()
                ->take(5)
                ->get(['id', 'client_id', 'business_name', 'package_amount', 'amount_paid', 'created_at']),
        ];
    }

    /** @return array<string, mixed> */
    private function finance(): array
    {
        return [
            'total_package_amount' => (float) Project::withLiveClient()->sum('package_amount'),
            'total_paid' => (float) Project::withLiveClient()->sum('amount_paid'),
            'total_due' => (float) Project::withLiveClient()->sum('amount_due'),
            'sales_this_month' => (float) ProjectSale::withLiveClient()->whereBetween(
                'sale_date',
                [now()->startOfMonth(), now()->endOfMonth()],
            )->sum('amount'),
            'upcoming_payments' => Project::withLiveClient()
                ->whereNotNull('next_payment_date')
                ->whereDate('next_payment_date', '>=', today())
                ->orderBy('next_payment_date')
                ->take(10)
                ->get(['id', 'business_name', 'next_payment_date', 'amount_due']),
            'recent_sales' => ProjectSale::withLiveClient()
                ->with(['project:id,business_name', 'employee:id,user_id', 'employee.user:id,name'])
                ->latest('sale_date')
                ->take(10)
                ->get(),
        ];
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return array<string, mixed>
     */
    private function employeeSection(User $user, Collection $employees): array
    {
        $employeeIds = $employees->pluck('id')->all();
        $primary = $employees->sortByDesc('joining_date')->first();

        return [
            'profile' => [
                'id' => $primary?->id,
                'name' => $user->name,
                'designation' => $primary?->designation?->name,
                'email' => $user->email,
            ],
            'employments' => $employees->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'company' => $employee->company?->name,
                'department' => $employee->department?->name,
                'designation' => $employee->designation?->name,
                'joining_date' => $employee->joining_date?->toDateString(),
                'is_active' => $employee->is_active,
            ])->values(),
            'assigned_projects' => Project::withLiveClient()
                ->whereIn('assigned_employee_id', $employeeIds)
                ->with('client:id,name')
                ->get([
                    'id', 'client_id', 'business_name', 'project_type',
                    'sales_target', 'achieved_sales', 'health_status',
                    'start_date', 'end_date',
                ]),
            'sales' => [
                'this_month' => (float) ProjectSale::withLiveClient()
                    ->whereIn('employee_id', $employeeIds)
                    ->whereBetween('sale_date', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('amount'),
                'all_time' => (float) ProjectSale::withLiveClient()
                    ->whereIn('employee_id', $employeeIds)
                    ->sum('amount'),
                'recent' => ProjectSale::withLiveClient()
                    ->whereIn('employee_id', $employeeIds)
                    ->with('project:id,business_name')
                    ->latest('sale_date')
                    ->take(10)
                    ->get(),
            ],
        ];
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @return list<array<string, mixed>>
     */
    private function teamLeaderSection(Collection $teams): array
    {
        return $teams->map(fn (Team $team) => [
            ...(new LedTeamResource($team))->resolve(),
            'sales_this_month' => (float) ProjectSale::withLiveClient()
                ->where('team_id', $team->id)
                ->whereBetween('sale_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
        ])->values()->all();
    }
}
