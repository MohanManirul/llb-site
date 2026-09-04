<?php

namespace App\Services\PublicApi;

use App\DTOs\FilterData;
use App\Enums\ContentStatus;
use App\Enums\ExamStage;
use App\Enums\QuestionType;
use App\Models\Program;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

final class QuestionArchiveService
{
    /**
     * @return Paginator<int, Question>
     */
    public function mcq(FilterData $filters): Paginator
    {
        return $this->baseQuery($filters, QuestionType::Mcq)
            ->whereNotNull('exam_year')
            ->with('options')
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return Paginator<int, Question>
     */
    public function written(FilterData $filters): Paginator
    {
        return $this->baseQuery($filters, QuestionType::Written)
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function filters(): array
    {
        $programIds = Question::query()
            ->publiclyVisible()
            ->join('subjects', 'subjects.id', '=', 'questions.subject_id')
            ->distinct()
            ->pluck('subjects.program_id');

        $years = Question::query()
            ->publiclyVisible()
            ->whereNotNull('exam_year')
            ->distinct()
            ->orderByDesc('exam_year')
            ->pluck('exam_year');

        return [
            [
                'key' => 'program',
                'label' => ['bn' => 'প্রোগ্রাম', 'en' => 'Program'],
                'options' => Program::query()
                    ->whereIn('id', $programIds)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (Program $program) => [
                        'value' => $program->slug,
                        'label' => $program->translated('name'),
                    ]),
            ],
            [
                'key' => 'subject',
                'label' => ['bn' => 'বিষয়', 'en' => 'Subject'],
                'options' => Subject::query()
                    ->where('is_active', true)
                    ->whereHas('questions', fn ($query) => $query->where('status', ContentStatus::Published))
                    ->with('program:id,slug')
                    ->orderBy('program_id')
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (Subject $subject) => [
                        'value' => $subject->slug,
                        'label' => $subject->translated('name'),
                        'program' => $subject->program->slug,
                    ]),
            ],
            [
                'key' => 'exam_stage',
                'label' => ['bn' => 'পরীক্ষার ধাপ', 'en' => 'Exam stage'],
                'options' => collect(ExamStage::labels())->map(fn ($option) => [
                    'value' => $option['value'],
                    'label' => ['bn' => $option['label_bn'], 'en' => $option['label_en']],
                ]),
            ],
            [
                'key' => 'exam_year',
                'label' => ['bn' => 'সাল', 'en' => 'Year'],
                'options' => $years->map(fn ($year) => [
                    'value' => (string) $year,
                    'label' => ['bn' => (string) $year, 'en' => (string) $year],
                ]),
            ],
        ];
    }

    private function baseQuery(FilterData $filters, QuestionType $type): Builder
    {
        return Question::query()
            ->publiclyVisible()
            ->where('type', $type)
            ->with(['subject:id,slug,name_bn,name_en,program_id', 'subject.program:id,slug,name_bn,name_en'])
            ->searchable($filters->search, ['question_bn', 'question_en'])
            ->filterable($filters->only(['exam_stage', 'exam_year']))
            ->when($filters->filter('program'), fn ($query, $slug) => $query
                ->whereHas('subject.program', fn ($q) => $q->where('slug', $slug)))
            ->when($filters->filter('subject'), fn ($query, $slug) => $query
                ->whereHas('subject', fn ($q) => $q->where('slug', $slug)))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->orderByDesc('id');
    }
}
