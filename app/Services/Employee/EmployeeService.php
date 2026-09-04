<?php

namespace App\Services\Employee;

use App\DTOs\FilterData;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EmployeeService
{
    /**
     * @return Paginator<int, Employee>
     */
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchOptions(?int $companyId, ?string $search = null, ?int $departmentId = null): Collection
    {
        if (! $companyId) {
            return collect();
        }

        $search = trim((string) $search);

        return Employee::query()
            ->with(['user:id,name,image', 'designation'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->when($search !== '', fn ($query) => $query->whereHas(
                'user',
                fn ($userQuery) => $userQuery->whereLike('name', "%{$search}%"),
            ))
            ->orderBy(User::query()->select('name')->whereColumn('users.id', 'employees.user_id'))
            ->limit(10)
            ->get(['id', 'user_id', 'designation_id'])
            ->map(fn (Employee $employee) => [
                'value' => $employee->id,
                'label' => $employee->user?->name,
                'description' => $employee->designation?->name,
                'image_url' => $employee->image_url,
                'thumbnail_url' => $employee->thumbnail_url,
            ]);
    }

    public function paginate(FilterData $filters): Paginator
    {
        $sortColumn = match ($filters->sortBy) {
            'designation' => Designation::query()
                ->select('name')
                ->whereColumn('designations.id', 'employees.designation_id'),
            'name' => User::query()
                ->select('name')
                ->whereColumn('users.id', 'employees.user_id'),
            'email' => User::query()
                ->select('email')
                ->whereColumn('users.id', 'employees.user_id'),
            default => $filters->sortBy,
        };

        return Employee::query()
            ->with(['user:id,name,email,phone,image', 'company', 'department', 'designation'])
            ->searchable($filters->search, [
                'user.name',
                'user.email',
                'user.phone',
                'company.name',
                'department.name',
                'designation.name',
            ])
            ->filterable($filters->only(['is_active', 'company_id', 'department_id']))
            ->orderBy($sortColumn, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    public function create(array $data): Employee
    {
        return DB::transaction(fn () => Employee::create($this->applyResignationStatus($data))->fresh(['user']));
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $employee->update($this->applyResignationStatus($data, $employee));

            return $employee->fresh(['user']);
        });
    }

    private function applyResignationStatus(array $data, ?Employee $employee = null): array
    {
        $resignationDate = array_key_exists('resignation_date', $data)
            ? $data['resignation_date']
            : $employee?->resignation_date;

        if ($resignationDate !== null && $resignationDate !== '') {
            $data['is_active'] = false;
        }

        return $data;
    }

    public function delete(Employee $employee): void
    {
        $employee->delete();
    }
}
