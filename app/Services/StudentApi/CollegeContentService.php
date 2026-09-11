<?php

namespace App\Services\StudentApi;

use App\DTOs\FilterData;
use App\Models\ClassNote;
use App\Models\ClassRoutine;
use App\Models\Notice;
use App\Models\Student;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\Paginator as BasePaginator;
use Illuminate\Support\Collection;

/**
 * Read-only access to the content of the college a student belongs to. A
 * student with no college sees nothing rather than everything.
 */
final class CollegeContentService
{
    /**
     * @return Paginator<int, Notice>
     */
    public function notices(Student $student, FilterData $filters): Paginator
    {
        if ($student->college_id === null) {
            return $this->emptyPaginator($filters);
        }

        return Notice::query()
            ->forCollege($student->college_id)
            ->unexpired()
            ->with(['subject:id,name_bn,name_en', 'session:id,label', 'teacher:id,name'])
            ->searchable($filters->search, ['title_bn', 'title_en', 'body_bn', 'body_en'])
            ->filterable($filters->only(['category']))
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return Paginator<int, ClassRoutine>
     */
    public function routines(Student $student, FilterData $filters): Paginator
    {
        if ($student->college_id === null) {
            return $this->emptyPaginator($filters);
        }

        return ClassRoutine::query()
            ->visibleToCollege($student->college_id)
            ->unexpired()
            ->with(['session:id,label', 'teacher:id,name'])
            ->searchable($filters->search, ['title_bn', 'title_en', 'description_bn', 'description_en'])
            ->orderByDesc('effective_from')
            ->orderByDesc('published_at')
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return Paginator<int, ClassNote>
     */
    public function notes(Student $student, FilterData $filters): Paginator
    {
        if ($student->college_id === null) {
            return $this->emptyPaginator($filters);
        }

        return ClassNote::query()
            ->visibleToCollege($student->college_id)
            ->unexpired()
            ->with(['subject:id,name_bn,name_en', 'session:id,label', 'teacher:id,name'])
            ->searchable($filters->search, ['title_bn', 'title_en', 'description_bn', 'description_en'])
            ->filterable($filters->only(['subject_id']))
            ->orderByDesc('published_at')
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function noteSubjects(Student $student): Collection
    {
        if ($student->college_id === null) {
            return collect();
        }

        return ClassNote::query()
            ->visibleToCollege($student->college_id)
            ->unexpired()
            ->with('subject:id,name_bn,name_en')
            ->get()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($subject) => [
                'value' => $subject->id,
                'label' => $subject->name_en ?? $subject->name_bn,
                'label_bn' => $subject->name_bn,
            ]);
    }

    /**
     * @return Paginator<int, never>
     */
    private function emptyPaginator(FilterData $filters): Paginator
    {
        return new BasePaginator([], $filters->perPage, $filters->page);
    }
}
