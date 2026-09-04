<?php

namespace App\Services\StudentApi;

use App\DTOs\FilterData;
use App\Models\ModelTest;
use App\Models\Student;
use Illuminate\Contracts\Pagination\Paginator;

final class StudentModelTestService
{
    public function paginate(Student $student, FilterData $filters): Paginator
    {
        return ModelTest::query()
            ->publiclyVisible()
            ->with('program:id,slug,name_bn,name_en')
            ->withCount('questions')
            ->with(['attempts' => fn ($query) => $query
                ->where('student_id', $student->id)
                ->orderByDesc('id')
                ->limit(1),
            ])
            ->when($filters->filter('program_id'), fn ($query, $id) => $query->where('program_id', $id))
            ->orderByDesc('published_at')
            ->simplePaginate($filters->perPage);
    }

    public function show(ModelTest $modelTest, Student $student): ModelTest
    {
        abort_unless($modelTest->isPubliclyVisible(), 404);

        return $modelTest
            ->load('program:id,slug,name_bn,name_en')
            ->loadCount('questions')
            ->load(['attempts' => fn ($query) => $query
                ->where('student_id', $student->id)
                ->orderByDesc('id'),
            ]);
    }
}
