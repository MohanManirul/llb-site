<?php

namespace Tests\Feature;

use App\Models\ModelTest;
use App\Models\Program;
use App\Models\Question;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentModelTestAttemptTest extends TestCase
{
    use RefreshDatabase;

    private Subject $subject;

    private Student $student;

    private ModelTest $test;

    protected function setUp(): void
    {
        parent::setUp();

        $program = Program::create([
            'slug' => 'bar-council', 'name_bn' => 'বার কাউন্সিল', 'name_en' => 'Bar Council',
            'has_levels' => false, 'has_exam_stages' => true, 'has_sessions' => false,
        ]);

        $this->subject = Subject::create([
            'program_id' => $program->id,
            'slug' => 'penal-code',
            'name_bn' => 'দণ্ডবিধি',
            'name_en' => 'Penal Code',
        ]);

        $this->student = Student::create([
            'name' => 'K', 'email' => 'karim@example.com', 'password' => 'secret-password',
        ]);

        $this->test = ModelTest::create([
            'slug' => 'bar-mcq-1',
            'title_bn' => 'বার কাউন্সিল মডেল টেস্ট',
            'program_id' => $program->id,
            'duration_minutes' => 60,
            'negative_mark' => 0.25,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        foreach (range(1, 10) as $index) {
            $question = Question::create([
                'type' => 'mcq',
                'subject_id' => $this->subject->id,
                'question_bn' => 'প্রশ্ন '.$index,
                'explanation_bn' => 'ব্যাখ্যা '.$index,
                'status' => 'published',
            ]);

            $question->options()->createMany([
                ['option_bn' => 'ক', 'is_correct' => true, 'sort_order' => 1],
                ['option_bn' => 'খ', 'is_correct' => false, 'sort_order' => 2],
                ['option_bn' => 'গ', 'is_correct' => false, 'sort_order' => 3],
            ]);

            $this->test->questions()->attach($question->id, ['sort_order' => $index, 'marks' => 1]);
        }
    }

    private function startAttempt(): array
    {
        return $this->actingAs($this->student, 'student')
            ->postJson('/v1/student/model-tests/bar-mcq-1/attempts')
            ->assertCreated()
            ->json('result');
    }

    public function test_the_public_catalogue_lists_only_published_tests(): void
    {
        ModelTest::create([
            'slug' => 'draft-test',
            'title_bn' => 'খসড়া টেস্ট',
            'program_id' => $this->test->program_id,
            'duration_minutes' => 30,
        ]);

        $this->getJson('/v1/public/model-tests')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.slug', 'bar-mcq-1');

        $this->getJson('/v1/public/model-tests/draft-test')->assertNotFound();
    }

    public function test_starting_an_attempt_returns_questions_without_answers(): void
    {
        $attempt = $this->startAttempt();

        $this->assertSame('in_progress', $attempt['status']);
        $this->assertCount(10, $attempt['questions']);
        $this->assertGreaterThan(3500, $attempt['remaining_seconds']);

        foreach ($attempt['questions'] as $question) {
            foreach ($question['options'] as $option) {
                $this->assertArrayNotHasKey('is_correct', $option);
            }

            $this->assertArrayNotHasKey('explanation', $question);
        }
    }

    public function test_starting_again_resumes_the_same_attempt(): void
    {
        $first = $this->startAttempt();

        $second = $this->actingAs($this->student, 'student')
            ->postJson('/v1/student/model-tests/bar-mcq-1/attempts')
            ->assertCreated()
            ->json('result');

        $this->assertSame($first['id'], $second['id']);
        $this->assertDatabaseCount('test_attempts', 1);
    }

    public function test_answers_are_saved_and_can_be_changed(): void
    {
        $attempt = $this->startAttempt();
        $question = $attempt['questions'][0];

        $this->actingAs($this->student, 'student')
            ->putJson("/v1/student/attempts/{$attempt['id']}/answers", [
                'question_id' => $question['id'],
                'question_option_id' => $question['options'][1]['id'],
            ])
            ->assertOk();

        $this->actingAs($this->student, 'student')
            ->putJson("/v1/student/attempts/{$attempt['id']}/answers", [
                'question_id' => $question['id'],
                'question_option_id' => $question['options'][0]['id'],
            ])
            ->assertOk();

        $this->assertDatabaseCount('attempt_answers', 1);
        $this->assertDatabaseHas('attempt_answers', [
            'question_id' => $question['id'],
            'question_option_id' => $question['options'][0]['id'],
        ]);
    }

    public function test_foreign_questions_and_options_are_rejected(): void
    {
        $attempt = $this->startAttempt();

        $foreign = Question::create([
            'type' => 'mcq',
            'subject_id' => $this->subject->id,
            'question_bn' => 'বাইরের প্রশ্ন',
            'status' => 'published',
        ]);
        $foreignOption = $foreign->options()->create([
            'option_bn' => 'ক', 'is_correct' => true, 'sort_order' => 1,
        ]);

        $this->actingAs($this->student, 'student')
            ->putJson("/v1/student/attempts/{$attempt['id']}/answers", [
                'question_id' => $foreign->id,
                'question_option_id' => $foreignOption->id,
            ])
            ->assertStatus(422);

        $question = $attempt['questions'][0];

        $this->actingAs($this->student, 'student')
            ->putJson("/v1/student/attempts/{$attempt['id']}/answers", [
                'question_id' => $question['id'],
                'question_option_id' => $foreignOption->id,
            ])
            ->assertStatus(422);
    }

    public function test_submit_scores_with_negative_marking(): void
    {
        $attempt = $this->startAttempt();
        $questions = $attempt['questions'];

        foreach (array_slice($questions, 0, 6) as $question) {
            $correct = collect($question['options'])->firstWhere('option.bn', 'ক');

            $this->actingAs($this->student, 'student')
                ->putJson("/v1/student/attempts/{$attempt['id']}/answers", [
                    'question_id' => $question['id'],
                    'question_option_id' => $correct['id'],
                ])->assertOk();
        }

        foreach (array_slice($questions, 6, 2) as $question) {
            $wrong = collect($question['options'])->firstWhere('option.bn', 'খ');

            $this->actingAs($this->student, 'student')
                ->putJson("/v1/student/attempts/{$attempt['id']}/answers", [
                    'question_id' => $question['id'],
                    'question_option_id' => $wrong['id'],
                ])->assertOk();
        }

        $this->actingAs($this->student, 'student')
            ->postJson("/v1/student/attempts/{$attempt['id']}/submit")
            ->assertOk()
            ->assertJsonPath('result.status', 'submitted')
            ->assertJsonPath('result.score', '5.50')
            ->assertJsonPath('result.correct_count', 6)
            ->assertJsonPath('result.wrong_count', 2)
            ->assertJsonPath('result.skipped_count', 2);
    }

    public function test_the_result_reveals_answers_only_after_submit(): void
    {
        $attempt = $this->startAttempt();

        $this->actingAs($this->student, 'student')
            ->getJson("/v1/student/attempts/{$attempt['id']}/result")
            ->assertStatus(422);

        $this->actingAs($this->student, 'student')
            ->postJson("/v1/student/attempts/{$attempt['id']}/submit")
            ->assertOk();

        $this->actingAs($this->student, 'student')
            ->getJson("/v1/student/attempts/{$attempt['id']}/result")
            ->assertOk()
            ->assertJsonPath('result.breakdown.0.options.0.is_correct', true)
            ->assertJsonPath('result.breakdown.0.explanation.bn', 'ব্যাখ্যা 1');
    }

    public function test_a_submitted_attempt_cannot_be_answered_or_resubmitted(): void
    {
        $attempt = $this->startAttempt();

        $this->actingAs($this->student, 'student')
            ->postJson("/v1/student/attempts/{$attempt['id']}/submit")
            ->assertOk();

        $question = $attempt['questions'][0];

        $this->actingAs($this->student, 'student')
            ->putJson("/v1/student/attempts/{$attempt['id']}/answers", [
                'question_id' => $question['id'],
                'question_option_id' => $question['options'][0]['id'],
            ])
            ->assertStatus(422);

        $this->actingAs($this->student, 'student')
            ->postJson("/v1/student/attempts/{$attempt['id']}/submit")
            ->assertStatus(422);
    }

    public function test_an_expired_attempt_is_finalized_from_saved_answers(): void
    {
        $attempt = $this->startAttempt();
        $question = $attempt['questions'][0];

        $this->actingAs($this->student, 'student')
            ->putJson("/v1/student/attempts/{$attempt['id']}/answers", [
                'question_id' => $question['id'],
                'question_option_id' => $question['options'][0]['id'],
            ])->assertOk();

        $this->travel(2)->hours();

        $this->actingAs($this->student, 'student')
            ->putJson("/v1/student/attempts/{$attempt['id']}/answers", [
                'question_id' => $question['id'],
                'question_option_id' => $question['options'][1]['id'],
            ])->assertStatus(422);

        $this->actingAs($this->student, 'student')
            ->getJson("/v1/student/attempts/{$attempt['id']}")
            ->assertOk()
            ->assertJsonPath('result.status', 'expired')
            ->assertJsonPath('result.score', '1.00')
            ->assertJsonPath('result.correct_count', 1);
    }

    public function test_another_students_attempt_is_not_found(): void
    {
        $attempt = $this->startAttempt();

        $other = Student::create([
            'name' => 'R', 'email' => 'rahim@example.com', 'password' => 'secret-password',
        ]);

        $this->actingAs($other, 'student')
            ->getJson("/v1/student/attempts/{$attempt['id']}")
            ->assertNotFound();

        $this->actingAs($other, 'student')
            ->postJson("/v1/student/attempts/{$attempt['id']}/submit")
            ->assertNotFound();
    }

    public function test_my_attempts_history_is_listed(): void
    {
        $attempt = $this->startAttempt();

        $this->actingAs($this->student, 'student')
            ->postJson("/v1/student/attempts/{$attempt['id']}/submit");

        $this->actingAs($this->student, 'student')
            ->getJson('/v1/student/attempts')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.model_test.slug', 'bar-mcq-1');
    }
}
