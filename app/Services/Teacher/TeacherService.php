<?php

namespace App\Services\Teacher;

use App\DTOs\FilterData;
use App\Models\Teacher;
use Illuminate\Contracts\Pagination\Paginator;

final class TeacherService
{
    /**
     * @return Paginator<int, Teacher>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return Teacher::query()
            ->with(['college:id,name_bn,name_en,slug', 'approver:id,name'])
            ->withCount(['notices', 'classRoutines', 'classNotes'])
            ->searchable($filters->search, ['name', 'email', 'phone'])
            ->filterable($filters->only(['is_active', 'college_id']))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->orderByDesc('id')
            ->simplePaginate($filters->perPage);
    }

    public function show(Teacher $teacher): Teacher
    {
        return $teacher
            ->load(['college:id,name_bn,name_en,slug', 'approver:id,name'])
            ->loadCount(['notices', 'classRoutines', 'classNotes']);
    }

    public function toggleActive(Teacher $teacher, ?int $approvedBy = null): Teacher
    {
        $activating = ! $teacher->is_active;

        $teacher->update([
            'is_active' => $activating,
            'approved_at' => $activating && $teacher->approved_at === null ? now() : $teacher->approved_at,
            'approved_by' => $activating && $teacher->approved_by === null ? $approvedBy : $teacher->approved_by,
        ]);

        return $teacher;
    }
}
