<?php

namespace App\Services\College;

use App\DTOs\FilterData;
use App\Models\College;
use App\Support\Slug;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

final class CollegeService
{
    /**
     * @return Paginator<int, College>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return College::query()
            ->withCount(['students', 'teachers'])
            ->searchable($filters->search, [
                'name_bn', 'name_en', 'short_name_bn', 'short_name_en',
                'eiin_code', 'college_code', 'district_en',
            ])
            ->filterable($filters->only(['is_active', 'district_en']))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function options(): Collection
    {
        return College::query()
            ->where('is_active', true)
            ->orderBy('name_en')
            ->orderBy('name_bn')
            ->get()
            ->map(fn (College $college) => [
                'value' => $college->id,
                'label' => $college->name_en ?? $college->name_bn,
                'label_bn' => $college->name_bn,
            ]);
    }

    public function create(array $data): College
    {
        $data['slug'] = Slug::for(College::class, $data['name_en'] ?? $data['name_bn'], fallbackPrefix: 'college');

        return College::create($data);
    }

    public function update(College $college, array $data): College
    {
        $college->update($data);

        return $college;
    }

    public function delete(College $college): void
    {
        $college->delete();
    }
}
