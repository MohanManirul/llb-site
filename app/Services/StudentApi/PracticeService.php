<?php

namespace App\Services\StudentApi;

use App\DTOs\FilterData;
use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\PracticeSession;
use App\Models\Question;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

final class PracticeService
{
    public function subjects(): Collection
    {
        $publishedMcq = fn ($query) => $query
            ->where('type', QuestionType::Mcq)
            ->where('status', ContentStatus::Published);

        return Subject::query()
            ->where('is_active', true)
            ->whereHas('questions', $publishedMcq)
            ->withCount(['questions as question_count' => $publishedMcq])
            ->with(['program:id,slug,name_bn,name_en', 'level:id,name_bn,name_en'])
            ->orderBy('program_id')
            ->orderBy('sort_order')
            ->get();
    }

    public function questions(array $filters, int $count): Collection
    {
        return Question::query()
            ->publiclyVisible()
            ->where('type', QuestionType::Mcq)
            ->where('subject_id', $filters['subject_id'])
            ->when($filters['exam_stage'] ?? null, fn ($query, $stage) => $query->where('exam_stage', $stage))
            ->when($filters['exam_year'] ?? null, fn ($query, $year) => $query->where('exam_year', $year))
            ->with('options')
            ->inRandomOrder()
            ->limit($count)
            ->get();
    }

    public function record(Student $student, array $data): PracticeSession
    {
        return $student->practiceSessions()->create($data);
    }

    public function history(Student $student, FilterData $filters): Paginator
    {
        return $student->practiceSessions()
            ->with('subject:id,slug,name_bn,name_en')
            ->orderByDesc('id')
            ->simplePaginate($filters->perPage);
    }
}
