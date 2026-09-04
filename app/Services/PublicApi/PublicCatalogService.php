<?php

namespace App\Services\PublicApi;

use App\Enums\ExamStage;
use App\Enums\MaterialType;
use App\Models\AcademicSession;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Support\Collection;

final class PublicCatalogService
{
    /**
     * @return Collection<int, Program>
     */
    public function programs(): Collection
    {
        return Program::query()
            ->where('is_active', true)
            ->withCount([
                'subjects' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function program(Program $program): Program
    {
        return $program
            ->load(['levels' => fn ($query) => $query->where('is_active', true)])
            ->loadCount(['subjects' => fn ($query) => $query->where('is_active', true)]);
    }

    /**
     * The server-built filter definitions for one program. The React filter
     * bar maps over these and renders one control per entry, so no program
     * shape is ever hardcoded on the client.
     *
     * @return array<int, array<string, mixed>>
     */
    public function filtersFor(Program $program): array
    {
        $filters = [];

        if ($program->has_levels) {
            $filters[] = [
                'key' => 'level',
                'label' => $program->translated('level_label'),
                'options' => $program->levels
                    ->where('is_active', true)
                    ->values()
                    ->map(fn ($level) => [
                        'value' => $level->slug,
                        'label' => $level->translated('name'),
                    ]),
            ];
        }

        if ($program->has_exam_stages) {
            $filters[] = [
                'key' => 'exam_stage',
                'label' => ['bn' => 'পরীক্ষার ধাপ', 'en' => 'Exam stage'],
                'options' => collect(ExamStage::labels())->map(fn ($option) => [
                    'value' => $option['value'],
                    'label' => ['bn' => $option['label_bn'], 'en' => $option['label_en']],
                ]),
            ];
        }

        if ($program->has_sessions) {
            $filters[] = [
                'key' => 'session',
                'label' => ['bn' => 'সেশন', 'en' => 'Session'],
                'options' => AcademicSession::query()
                    ->where('is_active', true)
                    ->orderByDesc('start_year')
                    ->get()
                    ->map(fn (AcademicSession $session) => [
                        'value' => $session->slug,
                        'label' => ['bn' => $session->label, 'en' => $session->label],
                    ]),
            ];
        }

        $filters[] = [
            'key' => 'type',
            'label' => ['bn' => 'ধরন', 'en' => 'Type'],
            'options' => collect(MaterialType::labels())->map(fn ($option) => [
                'value' => $option['value'],
                'label' => ['bn' => $option['label_bn'], 'en' => $option['label_en']],
            ]),
        ];

        return $filters;
    }

    /**
     * @return Collection<int, AcademicSession>
     */
    public function sessions(): Collection
    {
        return AcademicSession::query()
            ->where('is_active', true)
            ->orderByDesc('start_year')
            ->get();
    }

    /**
     * @return Collection<int, Subject>
     */
    public function subjects(?string $programSlug, ?string $levelSlug, ?string $search): Collection
    {
        $search = trim((string) $search);

        return Subject::query()
            ->with(['program:id,slug,name_bn,name_en', 'level:id,slug,name_bn,name_en'])
            ->where('is_active', true)
            ->whereHas('program', fn ($query) => $query->where('is_active', true))
            ->when($programSlug, fn ($query) => $query
                ->whereHas('program', fn ($q) => $q->where('slug', $programSlug)))
            ->when($levelSlug, fn ($query) => $query
                ->whereHas('level', fn ($q) => $q->where('slug', $levelSlug)))
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($q) => $q
                    ->whereLike('name_bn', "%{$search}%")
                    ->orWhereLike('name_en', "%{$search}%")))
            ->withCount([
                'materials as suggestions_count' => fn ($query) => $query->publiclyVisible()->where('type', MaterialType::Suggestion),
                'materials as books_count' => fn ($query) => $query->publiclyVisible()->where('type', MaterialType::Book),
                'materials as notes_count' => fn ($query) => $query->publiclyVisible()->where('type', MaterialType::Note),
            ])
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get();
    }

    public function subject(Subject $subject): Subject
    {
        return $subject
            ->load(['program:id,slug,name_bn,name_en,has_levels,has_exam_stages,has_sessions,level_label_bn,level_label_en', 'level:id,slug,name_bn,name_en'])
            ->loadCount([
                'materials as suggestions_count' => fn ($query) => $query->publiclyVisible()->where('type', MaterialType::Suggestion),
                'materials as books_count' => fn ($query) => $query->publiclyVisible()->where('type', MaterialType::Book),
                'materials as notes_count' => fn ($query) => $query->publiclyVisible()->where('type', MaterialType::Note),
            ]);
    }
}
