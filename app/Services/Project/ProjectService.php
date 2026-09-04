<?php

namespace App\Services\Project;

use App\Actions\Project\ReassignProjectAction;
use App\DTOs\FilterData;
use App\DTOs\ProjectListResult;
use App\Enums\AssignmentReason;
use App\Enums\BusinessStatus;
use App\Enums\ProjectType;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Services\Company\CompanyService;
use App\Services\Department\DepartmentService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ProjectService
{
    public function paginateForUser(FilterData $filters, User|Client|null $user): ProjectListResult
    {
        $employeeIds = $user instanceof User ? $user->employeeIds() : [];
        $ledTeamIds = $employeeIds !== []
            ? Team::ledByEmployees($employeeIds)->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        $query = $this->baseListQuery()->with(['client:id,name,email,phone']);

        if ($user instanceof Client) {
            $query->where('client_id', $user->id);
        } elseif ($user instanceof User && $user->hasRole('super-admin')) {
        } elseif ($user && $employeeIds !== []) {
            $query->where(function (Builder $scoped) use ($employeeIds, $ledTeamIds) {
                $scoped->whereIn('assigned_employee_id', $employeeIds);

                if ($ledTeamIds !== []) {
                    $scoped->orWhereIn('team_id', $ledTeamIds);
                }
            });
        } else {
            $query->whereKey([]);
        }

        if ($user instanceof User && $filters->hasFilter('scope')) {
            $this->applyOwnershipScope(
                $query,
                (string) $filters->filter('scope'),
                $employeeIds,
                $ledTeamIds,
            );
        }

        $this->applyPerformanceOrder($query, $filters);

        $projects = $this->applyListFilters($query, $filters, array_values(array_filter([
            'project_name', 'business_name',
            $this->canSeeContactDetails($user) ? 'contact_person' : null,
            $this->canSeeContactDetails($user) ? 'contact_email' : null,
            'company.name', 'department.name',
            $this->canSeeClientDetails($user) ? 'client.name' : null,
            'team.name', 'assignedEmployee.user.name',
        ])), ['company_id', 'department_id', 'team_id', 'client_id', 'health_status', 'business_status']);

        return new ProjectListResult($projects, $employeeIds, $ledTeamIds);
    }

    /**
     * @param  list<int>  $employeeIds
     * @param  list<int>  $ledTeamIds
     */
    private function applyOwnershipScope(
        Builder $query,
        string $scope,
        array $employeeIds,
        array $ledTeamIds,
    ): void {
        if ($scope === 'assigned') {
            $employeeIds === []
                ? $query->whereKey([])
                : $query->whereIn('assigned_employee_id', $employeeIds);

            return;
        }

        $ledTeamIds === []
            ? $query->whereKey([])
            : $query->whereIn('team_id', $ledTeamIds);
    }

    private function applyPerformanceOrder(Builder $query, FilterData $filters): void
    {
        $performance = $filters->filter('performance');

        if ($performance === null) {
            return;
        }

        [$monthStart, $monthEnd] = Project::currentMonthRange();

        $monthSales = '(SELECT COALESCE(SUM(sr.total_sales), 0) FROM sales_reports sr'
            .' WHERE sr.project_id = projects.id AND sr.week_start >= ? AND sr.week_end <= ?)';

        $monthlyTarget = 'NULLIF(projects.sales_target / NULLIF(COALESCE(projects.target_months, projects.contract_months, 12), 0), 0)';

        $query->orderByRaw(
            $monthSales.' / '.$monthlyTarget.' '.($performance === 'risk' ? 'asc' : 'desc').' NULLS LAST',
            [$monthStart, $monthEnd],
        );
    }

    public function paginateForClient(FilterData $filters, Client $client): ProjectListResult
    {
        $query = $this->baseListQuery()->where('client_id', $client->id);

        $projects = $this->applyListFilters($query, $filters, [
            'project_name', 'business_name',
            'company.name', 'department.name',
            'team.name', 'assignedEmployee.user.name',
        ], ['health_status', 'business_status']);

        return new ProjectListResult($projects);
    }

    public function canSeeClientDetails(User|Client|null $user): bool
    {
        if ($user instanceof Client) {
            return true;
        }

        return $user !== null && $user->can('view project client');
    }

    public function canSeeContactDetails(User|Client|null $user): bool
    {
        if ($user instanceof Client) {
            return true;
        }

        return $user !== null && $user->can('view project contact');
    }

    private function baseListQuery(): Builder
    {
        return Project::query()->withLiveClient()
            ->with([
                'company:id,name',
                'department:id,name',
                'team:id,name',
                'assignedEmployee:id,user_id',
                'assignedEmployee.user:id,name,email,phone',
            ]);
    }

    /**
     * @param  array<int, string>  $searchable
     * @param  array<int, string>  $filterable
     * @return Paginator<int, Project>
     */
    private function applyListFilters(Builder $query, FilterData $filters, array $searchable, array $filterable): Paginator
    {
        $projects = $this->paginate($query, $filters, $searchable, $filterable);

        $this->milestoneProgress->attachMany($projects->getCollection());

        return $projects;
    }

    /**
     * @param  array<int, string>  $searchable
     * @param  array<int, string>  $filterable
     * @return Paginator<int, Project>
     */
    private function paginate(Builder $query, FilterData $filters, array $searchable, array $filterable): Paginator
    {
        return $query
            ->searchable($filters->search, $searchable)
            ->filterable($filters->only($filterable))
            ->when(
                $filters->hasFilter('date_from'),
                fn (Builder $builder) => $builder->whereDate('created_at', '>=', $filters->filter('date_from')),
            )
            ->when(
                $filters->hasFilter('date_to'),
                fn (Builder $builder) => $builder->whereDate('created_at', '<=', $filters->filter('date_to')),
            )
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchTeamOptions(?int $companyId, ?string $search = null, ?int $departmentId = null): Collection
    {
        if (! $companyId) {
            return collect();
        }

        $search = trim((string) $search);

        return Team::query()
            ->where('company_id', $companyId)
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($search !== '', fn ($q) => $q->whereLike('name', "%{$search}%"))
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name'])
            ->map(fn (Team $team) => ['value' => $team->id, 'label' => $team->name]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchTeamMemberOptions(?int $teamId, ?string $search = null): Collection
    {
        if (! $teamId) {
            return collect();
        }

        $search = trim((string) $search);

        return Employee::query()
            ->whereIn('id', function ($sub) use ($teamId) {
                $sub->select('employee_id')
                    ->from('team_members')
                    ->where('team_id', $teamId)
                    ->whereNull('deleted_at');
            })
            ->when($search !== '', fn ($q) => $q->whereHas(
                'user',
                fn ($userQuery) => $userQuery->whereLike('name', "%{$search}%"),
            ))
            ->orderBy(User::query()->select('name')->whereColumn('users.id', 'employees.user_id'))
            ->limit(10)
            ->with(['user:id,name,image', 'designation'])
            ->get(['id', 'user_id', 'designation_id'])
            ->map(fn (Employee $employee) => [
                'value' => $employee->id,
                'label' => $employee->user?->name,
                'description' => $employee->designation?->name,
                'image_url' => $employee->user?->image_url,
                'thumbnail_url' => $employee->user?->thumbnail_url,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchClientOptions(?string $search = null): Collection
    {
        $search = trim((string) $search);

        return Client::query()
            ->when($search !== '', fn ($q) => $q->whereLike('name', "%{$search}%"))
            ->where('is_active', true)
            ->latest()
            ->get(['id', 'name', 'email', 'phone', 'image'])
            ->map(fn (Client $client) => [
                'value' => $client->id,
                'label' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'image_url' => $client->image_url,
                'thumbnail_url' => $client->thumbnail_url,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchCompanyOptions(?string $search = null): Collection
    {
        return $this->companyService->searchOptions($search);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchDepartmentOptions(?string $search = null, ?int $companyId = null): Collection
    {
        return $this->departmentService->searchOptions($search, $companyId);
    }

    public function __construct(
        private readonly ReassignProjectAction $reassign,
        private readonly MilestoneProgressService $milestoneProgress,
        private readonly CompanyService $companyService,
        private readonly DepartmentService $departmentService,
    ) {}

    public function create(array $data): Project
    {
        $team = Team::findOrFail($data['team_id']);

        $data['department_id'] = $team->department_id;
        $data['contract_days'] ??= 0;
        $data['end_date'] = $this->addDuration(
            $data['start_date'],
            $data['contract_months'],
            $data['contract_days'],
        );
        $initialPaid = (float) ($data['amount_paid'] ?? 0);
        $data['amount_paid'] = $initialPaid;
        $data['total_amount'] ??= $data['package_amount'];
        $data['amount_due'] = $data['total_amount'] - $initialPaid;

        if ($initialPaid > 0) {
            $data['last_payment_date'] = now()->toDateString();
        }

        $data = $this->resolveSalesTarget($data);

        return DB::transaction(function () use ($data, $initialPaid) {
            $project = Project::create($data);

            if ($initialPaid > 0) {
                $this->createInitialPayment($project, $initialPaid);
            }

            return $project;
        });
    }

    private function createInitialPayment(Project $project, float $amount): void
    {
        Payment::create([
            'project_id' => $project->id,
            'amount' => $amount,
            'payment_type' => 'cash',
            'payment_date' => now()->toDateString(),
            'notes' => 'Payment recorded during project creation',
            'created_by' => auth()->id() ?? 1,
        ]);
    }

    public function update(Project $project, array $data): Project
    {
        $team = Team::findOrFail($data['team_id']);
        $employeeId = $data['assigned_employee_id'] ?? null;
        $employee = $employeeId !== null
            ? Employee::findOrFail($employeeId)
            : null;

        $ownershipChanged = $project->team_id !== $team->id
            || $project->assigned_employee_id !== $employee?->id;

        $data['contract_days'] ??= 0;
        $data['end_date'] = $this->addDuration(
            $data['start_date'],
            $data['contract_months'],
            $data['contract_days'],
        );
        $currentPaid = (float) $project->amount_paid;
        $requestedPaid = (float) ($data['amount_paid'] ?? 0);
        $amountPaidDelta = $requestedPaid - $currentPaid;
        $finalPaid = $amountPaidDelta > 0 ? $requestedPaid : $currentPaid;
        $data['amount_paid'] = $finalPaid;
        $data['total_amount'] ??= $data['package_amount'];
        $data['amount_due'] = $data['total_amount'] - $finalPaid;

        if ($amountPaidDelta > 0) {
            $data['last_payment_date'] = now()->toDateString();
        }

        $data = $this->resolveSalesTarget($data);

        return DB::transaction(function () use ($project, $data, $team, $employee, $ownershipChanged, $amountPaidDelta): Project {
            $project->update(Arr::except($data, [
                'company_id', 'team_id', 'department_id', 'assigned_employee_id',
            ]));

            if ($amountPaidDelta > 0) {
                $this->createInitialPayment($project, $amountPaidDelta);
            }

            if (! $ownershipChanged) {
                return $project->refresh();
            }

            if ($employee !== null) {
                $this->reassign->execute(
                    $project->refresh(),
                    $team,
                    $employee,
                    AssignmentReason::Manual,
                );
            } else {
                $project->assignments()->whereNull('unassigned_at')->update([
                    'unassigned_at' => now(),
                ]);
                $project->update([
                    'team_id' => $team->id,
                    'department_id' => $team->department_id,
                    'assigned_employee_id' => null,
                ]);
            }

            return $project->refresh();
        });
    }

    public function updateBusinessStatus(Project $project, BusinessStatus $status): Project
    {
        $project->update(['business_status' => $status]);

        return $project->refresh();
    }

    private function resolveSalesTarget(array $data): array
    {
        if (($data['project_type'] ?? null) === ProjectType::ChallengeBased->value) {
            $data['target_start_date'] = $data['target_start_date'] ?? $data['start_date'];
            $data['target_days'] ??= 0;
            $data['target_deadline'] = $this->addDuration(
                $data['target_start_date'],
                $data['target_months'],
                $data['target_days'],
            );

            return $data;
        }

        $data['sales_target'] = null;
        $data['target_start_date'] = null;
        $data['target_months'] = null;
        $data['target_days'] = null;
        $data['target_deadline'] = null;

        return $data;
    }

    private function addDuration(string $date, int|string $months, int|string|null $days = 0): string
    {
        return CarbonImmutable::parse($date)
            ->addMonths((int) $months)
            ->addDays((int) $days)
            ->toDateString();
    }
}
