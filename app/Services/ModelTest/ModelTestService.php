<?php

namespace App\Services\ModelTest;

use App\DTOs\FilterData;
use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\ModelTest;
use App\Models\Question;
use App\Support\Slug;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ModelTestService
{
    /**
     * @return Paginator<int, ModelTest>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return ModelTest::query()
            ->with('program:id,name_bn,name_en,slug')
            ->withCount(['questions', 'attempts'])
            ->searchable($filters->search, ['title_bn', 'title_en'])
            ->filterable($filters->only(['status', 'program_id', 'exam_stage']))
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
            $counts[$status->value] = ModelTest::where('status', $status)->count();
        }

        return $counts;
    }

    public function create(array $data, ?int $userId = null): ModelTest
    {
        $data['slug'] = Slug::for(
            ModelTest::class,
            ($data['title_en'] ?? null) ?: $data['title_bn'],
            fallbackPrefix: 'model-test',
        );
        $data['status'] = ContentStatus::Draft;
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;

        return ModelTest::create($data);
    }

    public function update(ModelTest $modelTest, array $data, ?int $userId = null): ModelTest
    {
        unset($data['status'], $data['published_at']);
        $data['updated_by'] = $userId;

        $modelTest->update($data);

        return $modelTest;
    }

    public function publish(ModelTest $modelTest, ?int $userId = null): ModelTest
    {
        if ($modelTest->questions()->count() === 0) {
            throw ValidationException::withMessages([
                'status' => 'A model test cannot be published without questions.',
            ]);
        }

        if ($modelTest->questions()->where('status', '!=', ContentStatus::Published)->exists()) {
            throw ValidationException::withMessages([
                'status' => 'All attached questions must be published first.',
            ]);
        }

        $modelTest->update([
            'status' => ContentStatus::Published,
            'published_at' => $modelTest->published_at ?? now(),
            'updated_by' => $userId,
        ]);

        return $modelTest;
    }

    public function unpublish(ModelTest $modelTest, ContentStatus $status, ?int $userId = null): ModelTest
    {
        $modelTest->update([
            'status' => $status,
            'updated_by' => $userId,
        ]);

        return $modelTest;
    }

    public function delete(ModelTest $modelTest): void
    {
        $modelTest->delete();
    }

    /**
     * @param  array<int, int>  $questionIds
     */
    public function attachQuestions(ModelTest $modelTest, array $questionIds, float $marks = 1): ModelTest
    {
        $this->guardMutable($modelTest);

        return DB::transaction(function () use ($modelTest, $questionIds, $marks) {
            $eligibleIds = Question::query()
                ->whereIn('id', $questionIds)
                ->where('type', QuestionType::Mcq)
                ->where('status', ContentStatus::Published)
                ->pluck('id');

            if ($eligibleIds->count() !== count($questionIds)) {
                throw ValidationException::withMessages([
                    'question_ids' => 'Only published MCQ questions can be attached.',
                ]);
            }

            $attachedIds = $modelTest->questions()->pluck('questions.id');
            $nextOrder = (int) $modelTest->questions()->max('model_test_questions.sort_order');

            foreach ($eligibleIds->diff($attachedIds)->values() as $questionId) {
                $modelTest->questions()->attach($questionId, [
                    'sort_order' => ++$nextOrder,
                    'marks' => $marks,
                ]);
            }

            return $modelTest->load('questions.options');
        });
    }

    public function detachQuestion(ModelTest $modelTest, Question $question): void
    {
        $this->guardMutable($modelTest);

        $modelTest->questions()->detach($question->id);
    }

    /**
     * @param  array<int, int>  $questionIds
     * @param  array<int|string, mixed>  $marks
     */
    public function reorder(ModelTest $modelTest, array $questionIds, array $marks = []): ModelTest
    {
        $this->guardMutable($modelTest);

        return DB::transaction(function () use ($modelTest, $questionIds, $marks) {
            foreach (array_values($questionIds) as $index => $questionId) {
                $pivot = ['sort_order' => $index + 1];

                if (isset($marks[$questionId])) {
                    $pivot['marks'] = $marks[$questionId];
                }

                $modelTest->questions()->updateExistingPivot($questionId, $pivot);
            }

            return $modelTest->load('questions.options');
        });
    }

    private function guardMutable(ModelTest $modelTest): void
    {
        if ($modelTest->attempts()->exists()) {
            throw ValidationException::withMessages([
                'model_test' => 'This test already has attempts; duplicate it to make structural changes.',
            ]);
        }
    }
}
