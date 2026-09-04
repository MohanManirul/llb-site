<?php

namespace App\Services\Department;

use App\DTOs\FilterData;
use App\Models\Department;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

final class DepartmentService
{
    /**
     * @return Paginator<int, Department>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return Department::query()
            ->with('company')
            ->searchable($filters->search, ['name', 'description', 'company.name'])
            ->filterable($filters->only(['company_id']))
            ->when(
                $filters->hasFilter('is_active'),
                fn ($query) => $query->where('is_active', $filters->filter('is_active') === 'active'),
            )
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    public function create(array $data): Department
    {
        return Department::create($data);
    }

    public function update(Department $department, array $data): Department
    {
        $department->update($data);

        return $department;
    }

    public function delete(Department $department): void
    {
        $department->delete();
    }

    /**
     * @return Collection<int, array{value: int, label: string, description: ?string}>
     */
    public function searchOptions(?string $search = null, int|string|null $companyId = null): Collection
    {
        $search = trim((string) $search);

        return Department::query()
            ->where('is_active', true)
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($search !== '', fn ($query) => $query->whereLike('name', "%{$search}%"))
            ->latest()
            ->get()
            ->map(fn (Department $department) => [
                'value' => $department->id,
                'label' => $department->name,
                'description' => $department->description,
            ]);
    }
}
