<?php

namespace App\Services\Question;

use App\DTOs\FilterData;
use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\Question;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class QuestionService
{
    /**
     * @return Paginator<int, Question>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return Question::query()
            ->with([
                'subject:id,name_bn,name_en,program_id,program_level_id',
                'subject.program:id,name_bn,name_en,slug',
            ])
            ->withCount('options')
            ->searchable($filters->search, [
                'question_bn', 'question_en', 'reference', 'subject.name_bn', 'subject.name_en',
            ])
            ->filterable($filters->only([
                'type', 'status', 'subject_id', 'exam_stage', 'exam_year',
            ]))
            ->when($filters->filter('program_id'), fn ($query, $programId) => $query
                ->whereHas('subject', fn ($q) => $q->where('program_id', $programId)))
            ->when($filters->filter('exclude_model_test'), fn ($query, $modelTestId) => $query
                ->whereDoesntHave('modelTests', fn ($q) => $q->where('model_tests.id', $modelTestId)))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->orderByDesc('id')
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $counts = [];

        foreach (ContentStatus::cases() as $status) {
            $counts[$status->value] = Question::where('status', $status)->count();
        }

        return $counts;
    }

    public function create(array $data, array $options, ?int $userId = null): Question
    {
        $data['status'] = ContentStatus::Draft;
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;

        return DB::transaction(function () use ($data, $options) {
            $question = Question::create($data);

            foreach (array_values($options) as $index => $option) {
                $question->options()->create([
                    'option_bn' => $option['option_bn'],
                    'option_en' => $option['option_en'] ?? null,
                    'is_correct' => (bool) ($option['is_correct'] ?? false),
                    'sort_order' => $index + 1,
                ]);
            }

            return $question->load('options');
        });
    }

    public function update(Question $question, array $data, ?array $options, ?int $userId = null): Question
    {
        unset($data['status']);
        $data['updated_by'] = $userId;

        return DB::transaction(function () use ($question, $data, $options) {
            $question->update($data);

            if ($question->type === QuestionType::Written) {
                $question->options()->delete();
            } elseif ($options !== null) {
                $ownIds = $question->options()->pluck('id')->all();
                $keptIds = collect($options)->pluck('id')->filter()->all();
                $question->options()->whereNotIn('id', $keptIds)->delete();

                foreach (array_values($options) as $index => $option) {
                    $optionId = in_array($option['id'] ?? null, $ownIds, false) ? $option['id'] : null;

                    $question->options()->updateOrCreate(
                        ['id' => $optionId],
                        [
                            'option_bn' => $option['option_bn'],
                            'option_en' => $option['option_en'] ?? null,
                            'is_correct' => (bool) ($option['is_correct'] ?? false),
                            'sort_order' => $index + 1,
                        ],
                    );
                }
            }

            return $question->load('options');
        });
    }

    public function publish(Question $question, ?int $userId = null): Question
    {
        if ($question->type === QuestionType::Mcq
            && $question->options()->where('is_correct', true)->count() !== 1) {
            throw ValidationException::withMessages([
                'status' => 'An MCQ cannot be published without exactly one correct option.',
            ]);
        }

        $question->update([
            'status' => ContentStatus::Published,
            'updated_by' => $userId,
        ]);

        return $question;
    }

    public function unpublish(Question $question, ContentStatus $status, ?int $userId = null): Question
    {
        $question->update([
            'status' => $status,
            'updated_by' => $userId,
        ]);

        return $question;
    }

    public function delete(Question $question): void
    {
        $question->delete();
    }
}
