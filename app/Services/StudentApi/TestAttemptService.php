<?php

namespace App\Services\StudentApi;

use App\DTOs\FilterData;
use App\Enums\AttemptStatus;
use App\Models\AttemptAnswer;
use App\Models\ModelTest;
use App\Models\Student;
use App\Models\TestAttempt;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TestAttemptService
{
    private const int SUBMIT_GRACE_SECONDS = 30;

    public function start(Student $student, ModelTest $modelTest): TestAttempt
    {
        abort_unless($modelTest->isPubliclyVisible(), 404);

        if ($modelTest->questions()->count() === 0) {
            throw ValidationException::withMessages([
                'model_test' => 'This model test has no questions yet.',
            ]);
        }

        return DB::transaction(function () use ($student, $modelTest) {
            $existing = TestAttempt::query()
                ->where('student_id', $student->id)
                ->where('model_test_id', $modelTest->id)
                ->where('status', AttemptStatus::InProgress)
                ->lockForUpdate()
                ->first();

            if ($existing !== null && ! $existing->isPastExpiry()) {
                return $existing;
            }

            if ($existing !== null) {
                $this->finalize($existing, AttemptStatus::Expired);
            }

            return TestAttempt::create([
                'student_id' => $student->id,
                'model_test_id' => $modelTest->id,
                'status' => AttemptStatus::InProgress,
                'active' => true,
                'started_at' => now(),
                'expires_at' => now()->addMinutes($modelTest->duration_minutes),
            ]);
        });
    }

    public function paginate(Student $student, FilterData $filters): Paginator
    {
        return $student->attempts()
            ->with('modelTest:id,slug,title_bn,title_en,duration_minutes,negative_mark')
            ->orderByDesc('id')
            ->simplePaginate($filters->perPage);
    }

    public function show(TestAttempt $attempt): TestAttempt
    {
        if ($attempt->status === AttemptStatus::InProgress && $attempt->isPastExpiry()) {
            $attempt = DB::transaction(fn () => $this->finalize(
                TestAttempt::query()->whereKey($attempt->id)->lockForUpdate()->first(),
                AttemptStatus::Expired,
            ));
        }

        return $attempt->load([
            'modelTest' => fn ($query) => $query->with('program:id,slug,name_bn,name_en'),
            'answers',
        ]);
    }

    public function saveAnswer(TestAttempt $attempt, int $questionId, ?int $optionId): AttemptAnswer
    {
        if ($attempt->status !== AttemptStatus::InProgress || $attempt->isPastExpiry()) {
            throw ValidationException::withMessages([
                'attempt' => 'This attempt is no longer accepting answers.',
            ]);
        }

        $question = $attempt->modelTest->questions()
            ->whereKey($questionId)
            ->with('options')
            ->first();

        if ($question === null) {
            throw ValidationException::withMessages([
                'question_id' => 'This question does not belong to the test.',
            ]);
        }

        if ($optionId !== null && ! $question->options->contains('id', $optionId)) {
            throw ValidationException::withMessages([
                'question_option_id' => 'This option does not belong to the question.',
            ]);
        }

        return AttemptAnswer::updateOrCreate(
            ['test_attempt_id' => $attempt->id, 'question_id' => $questionId],
            ['question_option_id' => $optionId],
        );
    }

    public function submit(TestAttempt $attempt): TestAttempt
    {
        return DB::transaction(function () use ($attempt) {
            $locked = TestAttempt::query()->whereKey($attempt->id)->lockForUpdate()->first();

            if ($locked->status !== AttemptStatus::InProgress) {
                throw ValidationException::withMessages([
                    'attempt' => 'This attempt has already been finalized.',
                ]);
            }

            $status = now()->gt($locked->expires_at->addSeconds(self::SUBMIT_GRACE_SECONDS))
                ? AttemptStatus::Expired
                : AttemptStatus::Submitted;

            return $this->finalize($locked, $status);
        });
    }

    public function result(TestAttempt $attempt): TestAttempt
    {
        if ($attempt->status === AttemptStatus::InProgress && $attempt->isPastExpiry()) {
            $attempt = DB::transaction(fn () => $this->finalize(
                TestAttempt::query()->whereKey($attempt->id)->lockForUpdate()->first(),
                AttemptStatus::Expired,
            ));
        }

        if ($attempt->status === AttemptStatus::InProgress) {
            throw ValidationException::withMessages([
                'attempt' => 'This attempt has not been submitted yet.',
            ]);
        }

        return $attempt->load([
            'modelTest' => fn ($query) => $query->with(['program:id,slug,name_bn,name_en', 'questions.options']),
            'answers',
        ]);
    }

    private function finalize(TestAttempt $attempt, AttemptStatus $status): TestAttempt
    {
        $questions = $attempt->modelTest->questions()->with('options')->get();
        $answers = $attempt->answers()->get()->keyBy('question_id');

        $score = 0.0;
        $correct = 0;
        $wrong = 0;
        $skipped = 0;

        foreach ($questions as $question) {
            $answer = $answers->get($question->id);

            if ($answer === null || $answer->question_option_id === null) {
                $skipped++;

                continue;
            }

            $isCorrect = $question->options->contains(
                fn ($option) => $option->id === $answer->question_option_id && $option->is_correct,
            );

            if ($isCorrect) {
                $correct++;
                $score += (float) $question->pivot->marks;
            } else {
                $wrong++;
                $score -= (float) $attempt->modelTest->negative_mark;
            }

            $answer->update(['is_correct' => $isCorrect]);
        }

        $attempt->update([
            'status' => $status,
            'active' => null,
            'submitted_at' => now(),
            'score' => round($score, 2),
            'correct_count' => $correct,
            'wrong_count' => $wrong,
            'skipped_count' => $skipped,
        ]);

        return $attempt->refresh();
    }
}
