<?php

namespace App\Services\Academic;

use App\DTOs\FilterData;
use App\Models\Program;
use App\Models\Subject;
use App\Support\Slug;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

final class SubjectService
{
    /**
     * @return Paginator<int, Subject>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return Subject::query()
            ->with(['program:id,name_bn,name_en,slug', 'level:id,name_bn,name_en'])
            ->searchable($filters->search, ['name_bn', 'name_en', 'code'])
            ->filterable($filters->only(['program_id', 'program_level_id', 'is_active']))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function options(?string $search, ?int $programId, ?int $levelId): Collection
    {
        $search = trim((string) $search);

        return Subject::query()
            ->where('is_active', true)
            ->when($programId, fn ($query) => $query->where('program_id', $programId))
            ->when($levelId, fn ($query) => $query->where('program_level_id', $levelId))
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($q) => $q
                    ->whereLike('name_bn', "%{$search}%")
                    ->orWhereLike('name_en', "%{$search}%")
                    ->orWhereLike('code', "%{$search}%")))
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->limit(20)
            ->get(['id', 'name_bn', 'name_en', 'code'])
            ->map(fn (Subject $subject) => [
                'value' => $subject->id,
                'label' => $subject->name_en,
                'label_bn' => $subject->name_bn,
                'description' => $subject->code,
            ]);
    }

    public function create(array $data): Subject
    {
        $data['slug'] = $this->slugFor($data);

        return Subject::create($data);
    }

    public function update(Subject $subject, array $data): Subject
    {
        $subject->update($data);

        return $subject;
    }

    public function delete(Subject $subject): void
    {
        $subject->delete();
    }

    private function slugFor(array $data): string
    {
        $program = Program::find($data['program_id'] ?? null);

        return Slug::for(
            Subject::class,
            $data['name_en'] ?? $data['name_bn'],
            fallbackPrefix: 'subject',
            suffixes: $program ? [$program->slug] : [],
        );
    }
}
