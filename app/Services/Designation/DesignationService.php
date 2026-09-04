<?php

namespace App\Services\Designation;

use App\DTOs\FilterData;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class DesignationService
{
    /**
     * @return Paginator<int, Designation>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return Designation::query()
            ->searchable($filters->search, ['name'])
            ->when(
                $filters->hasFilter('is_active'),
                fn (Builder $query) => $query->where('is_active', $filters->filter('is_active') === 'active'),
            )
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    public function create(array $data): Designation
    {
        return Designation::create($data);
    }

    public function update(Designation $designation, array $data): Designation
    {
        $designation->update($data);

        return $designation;
    }

    public function delete(Designation $designation): void
    {
        $designation->delete();
    }

    /**
     * @return Collection<int, array{value: int, label: string}>
     */
    public function searchOptions(?string $search = null, ?int $departmentId = null): Collection
    {
        $search = trim((string) $search);

        $query = fn (): Builder => Designation::query()
            ->where('is_active', true)
            ->when($search !== '', fn (Builder $scoped) => $scoped->whereLike('name', "%{$search}%"))
            ->latest();

        if ($departmentId !== null) {
            $held = $query()
                ->whereIn('id', Employee::query()
                    ->where('department_id', $departmentId)
                    ->whereNotNull('designation_id')
                    ->select('designation_id'))
                ->get();

            if ($held->isNotEmpty()) {
                return $this->toOptions($held);
            }
        }

        return $this->toOptions($query()->get());
    }

    /**
     * @param  Collection<int, Designation>  $designations
     * @return Collection<int, array{value: int, label: string}>
     */
    private function toOptions(Collection $designations): Collection
    {
        return $designations->map(fn (Designation $designation) => [
            'value' => $designation->id,
            'label' => $designation->name,
        ]);
    }
}
