<?php

namespace App\Services\Student;

use App\DTOs\FilterData;
use App\Models\Student;
use Illuminate\Contracts\Pagination\Paginator;

final class StudentService
{
    /**
     * @return Paginator<int, Student>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return Student::query()
            ->with('program:id,name_bn,name_en,slug')
            ->withCount(['attempts', 'practiceSessions'])
            ->searchable($filters->search, ['name', 'email', 'phone'])
            ->filterable($filters->only(['is_active', 'program_id']))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->orderByDesc('id')
            ->simplePaginate($filters->perPage);
    }

    public function show(Student $student): Student
    {
        return $student
            ->load('program:id,name_bn,name_en,slug')
            ->loadCount(['attempts', 'practiceSessions']);
    }

    public function toggleActive(Student $student): Student
    {
        $student->update(['is_active' => ! $student->is_active]);

        return $student;
    }
}
