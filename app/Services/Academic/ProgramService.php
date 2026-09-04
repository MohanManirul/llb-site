<?php

namespace App\Services\Academic;

use App\DTOs\FilterData;
use App\Models\Program;
use App\Support\Slug;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

final class ProgramService
{
    /**
     * @return Paginator<int, Program>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return Program::query()
            ->withCount(['levels', 'subjects'])
            ->searchable($filters->search, ['name_bn', 'name_en', 'short_name_bn', 'short_name_en'])
            ->filterable($filters->only(['is_active']))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function options(): Collection
    {
        return Program::query()
            ->with('levels')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Program $program) => [
                'value' => $program->id,
                'label' => $program->name_en,
                'label_bn' => $program->name_bn,
                'slug' => $program->slug,
                'has_levels' => $program->has_levels,
                'has_exam_stages' => $program->has_exam_stages,
                'has_sessions' => $program->has_sessions,
                'level_label' => $program->translated('level_label'),
                'levels' => $program->levels
                    ->where('is_active', true)
                    ->values()
                    ->map(fn ($level) => [
                        'value' => $level->id,
                        'label' => $level->name_en,
                        'label_bn' => $level->name_bn,
                    ]),
            ]);
    }

    public function create(array $data): Program
    {
        $data['slug'] = Slug::for(Program::class, $data['name_en'] ?? $data['name_bn'], fallbackPrefix: 'program');

        return Program::create($data);
    }

    public function update(Program $program, array $data): Program
    {
        $program->update($data);

        return $program;
    }

    public function delete(Program $program): void
    {
        $program->delete();
    }
}
