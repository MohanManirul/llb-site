<?php

namespace Tests\Feature;

use App\Models\ModelTest;
use App\Models\Program;
use App\Models\Question;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiModelTestBuilderTest extends TestCase
{
    use RefreshDatabase;

    private Program $program;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->program = Program::create([
            'slug' => 'bar-council', 'name_bn' => 'বার কাউন্সিল', 'name_en' => 'Bar Council',
            'has_levels' => false, 'has_exam_stages' => true, 'has_sessions' => false,
        ]);

        $this->subject = Subject::create([
            'program_id' => $this->program->id,
            'slug' => 'penal-code',
            'name_bn' => 'দণ্ডবিধি',
            'name_en' => 'Penal Code',
        ]);
    }

    private function makeQuestion(string $status = 'published', string $type = 'mcq'): Question
    {
        $question = Question::create([
            'type' => $type,
            'subject_id' => $this->subject->id,
            'question_bn' => 'প্রশ্ন '.uniqid(),
            'status' => $status,
        ]);

        if ($type === 'mcq') {
            foreach ([false, true, false, false] as $index => $isCorrect) {
                $question->options()->create([
                    'option_bn' => 'অপশন '.($index + 1),
                    'is_correct' => $isCorrect,
                    'sort_order' => $index + 1,
                ]);
            }
        }

        return $question;
    }

    private function makeTest(): ModelTest
    {
        return ModelTest::create([
            'slug' => 'bar-mcq-'.uniqid(),
            'title_bn' => 'বার কাউন্সিল মডেল টেস্ট',
            'program_id' => $this->program->id,
            'duration_minutes' => 60,
            'negative_mark' => 0.25,
        ]);
    }

    public function test_a_model_test_is_created_as_a_draft_with_a_slug(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/model-tests', [
                'title_bn' => 'বার কাউন্সিল মডেল টেস্ট ১',
                'title_en' => 'Bar Council Model Test 1',
                'program_id' => $this->program->id,
                'duration_minutes' => 60,
                'negative_mark' => 0.25,
            ])
            ->assertCreated()
            ->assertJsonPath('result.status', 'draft')
            ->assertJsonPath('result.slug', 'bar-council-model-test-1');
    }

    public function test_only_published_mcq_questions_can_be_attached(): void
    {
        $admin = User::factory()->create();
        $test = $this->makeTest();

        $published = $this->makeQuestion();
        $draft = $this->makeQuestion('draft');
        $written = $this->makeQuestion('published', 'written');

        $this->actingAs($admin)
            ->postJson("/v1/admin/model-tests/{$test->id}/questions", [
                'question_ids' => [$published->id, $draft->id],
            ])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->postJson("/v1/admin/model-tests/{$test->id}/questions", [
                'question_ids' => [$written->id],
            ])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->postJson("/v1/admin/model-tests/{$test->id}/questions", [
                'question_ids' => [$published->id],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'result.questions');
    }

    public function test_attaching_twice_does_not_duplicate(): void
    {
        $admin = User::factory()->create();
        $test = $this->makeTest();
        $question = $this->makeQuestion();

        $this->actingAs($admin)->postJson("/v1/admin/model-tests/{$test->id}/questions", [
            'question_ids' => [$question->id],
        ])->assertOk();

        $this->actingAs($admin)->postJson("/v1/admin/model-tests/{$test->id}/questions", [
            'question_ids' => [$question->id],
        ])->assertOk()->assertJsonCount(1, 'result.questions');
    }

    public function test_reorder_persists_sort_order_and_marks(): void
    {
        $admin = User::factory()->create();
        $test = $this->makeTest();

        $first = $this->makeQuestion();
        $second = $this->makeQuestion();

        $this->actingAs($admin)->postJson("/v1/admin/model-tests/{$test->id}/questions", [
            'question_ids' => [$first->id, $second->id],
        ])->assertOk();

        $this->actingAs($admin)
            ->patchJson("/v1/admin/model-tests/{$test->id}/questions/reorder", [
                'question_ids' => [$second->id, $first->id],
                'marks' => [$second->id => 2],
            ])
            ->assertOk()
            ->assertJsonPath('result.questions.0.id', $second->id);

        $this->assertEquals(2, $test->questions()->first()->pivot->marks);
    }

    public function test_publish_requires_questions_and_all_of_them_published(): void
    {
        $admin = User::factory()->create();
        $test = $this->makeTest();

        $this->actingAs($admin)
            ->patchJson("/v1/admin/model-tests/{$test->id}/publish")
            ->assertStatus(422);

        $question = $this->makeQuestion();

        $this->actingAs($admin)->postJson("/v1/admin/model-tests/{$test->id}/questions", [
            'question_ids' => [$question->id],
        ]);

        $question->update(['status' => 'draft']);

        $this->actingAs($admin)
            ->patchJson("/v1/admin/model-tests/{$test->id}/publish")
            ->assertStatus(422);

        $question->update(['status' => 'published']);

        $this->actingAs($admin)
            ->patchJson("/v1/admin/model-tests/{$test->id}/publish")
            ->assertOk()
            ->assertJsonPath('result.status', 'published');
    }

    public function test_structural_changes_are_frozen_once_an_attempt_exists(): void
    {
        $admin = User::factory()->create();
        $test = $this->makeTest();
        $question = $this->makeQuestion();

        $this->actingAs($admin)->postJson("/v1/admin/model-tests/{$test->id}/questions", [
            'question_ids' => [$question->id],
        ])->assertOk();

        $student = Student::create([
            'name' => 'K', 'email' => 'karim@example.com', 'password' => 'secret-password',
        ]);

        TestAttempt::create([
            'student_id' => $student->id,
            'model_test_id' => $test->id,
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'active' => true,
        ]);

        $other = $this->makeQuestion();

        $this->actingAs($admin)->postJson("/v1/admin/model-tests/{$test->id}/questions", [
            'question_ids' => [$other->id],
        ])->assertStatus(422);

        $this->actingAs($admin)
            ->deleteJson("/v1/admin/model-tests/{$test->id}/questions/{$question->id}")
            ->assertStatus(422);
    }
}
