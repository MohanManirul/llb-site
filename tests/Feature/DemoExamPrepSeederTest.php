<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\ModelTest;
use App\Models\PracticeSession;
use App\Models\Question;
use App\Models\Student;
use App\Models\TestAttempt;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\DemoExamPrepSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoExamPrepSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AcademicStructureSeeder::class);
        $this->seed(DemoExamPrepSeeder::class);
    }

    public function test_it_seeds_questions_in_every_state_with_exactly_one_correct_option(): void
    {
        $this->assertGreaterThan(0, Question::where('type', QuestionType::Mcq)->where('status', ContentStatus::Published)->count());
        $this->assertGreaterThan(0, Question::where('type', QuestionType::Written)->where('status', ContentStatus::Published)->count());
        $this->assertGreaterThan(0, Question::where('status', ContentStatus::Draft)->count());
        $this->assertGreaterThan(0, Question::where('status', ContentStatus::Archived)->count());

        foreach (Question::where('type', QuestionType::Mcq)->with('options')->get() as $question) {
            $this->assertGreaterThanOrEqual(2, $question->options->count());
            $this->assertSame(1, $question->options->where('is_correct', true)->count());
        }

        $this->assertSame(0, Question::where('type', QuestionType::Written)->has('options')->count());
    }

    public function test_published_model_tests_only_carry_published_mcq_questions(): void
    {
        $published = ModelTest::where('status', ContentStatus::Published)->with('questions')->get();

        $this->assertGreaterThan(0, $published->count());
        $this->assertGreaterThan(0, ModelTest::where('status', ContentStatus::Draft)->count());

        foreach ($published as $modelTest) {
            $this->assertGreaterThan(0, $modelTest->questions->count());

            foreach ($modelTest->questions as $question) {
                $this->assertSame(QuestionType::Mcq, $question->type);
                $this->assertSame(ContentStatus::Published, $question->status);
            }
        }
    }

    public function test_students_can_log_in_with_the_documented_password(): void
    {
        $student = Student::where('email', 'student1@example.com')->first();

        $this->assertNotNull($student);
        $this->assertTrue($student->is_active);
        $this->assertGreaterThan(0, Student::where('is_active', false)->count());

        $this->postJson('/v1/student/auth/login', [
            'email' => 'student1@example.com',
            'password' => DemoExamPrepSeeder::STUDENT_PASSWORD,
        ])->assertOk();
    }

    public function test_attempt_totals_match_their_answers(): void
    {
        $this->assertGreaterThan(0, TestAttempt::where('status', AttemptStatus::Submitted)->count());
        $this->assertGreaterThan(0, TestAttempt::where('status', AttemptStatus::Expired)->count());
        $this->assertSame(0, TestAttempt::where('status', AttemptStatus::InProgress)->count());

        foreach (TestAttempt::with(['answers', 'modelTest.questions'])->get() as $attempt) {
            $correct = $attempt->answers->where('is_correct', true)->count();
            $wrong = $attempt->answers->where('is_correct', false)->count();
            $total = $attempt->modelTest->questions->count();

            $this->assertSame($correct, $attempt->correct_count);
            $this->assertSame($wrong, $attempt->wrong_count);
            $this->assertSame($total - $correct - $wrong, $attempt->skipped_count);

            $expected = $correct * 1 - $wrong * (float) $attempt->modelTest->negative_mark;
            $this->assertEqualsWithDelta($expected, (float) $attempt->score, 0.001);
        }

        $this->assertGreaterThan(0, PracticeSession::count());
    }

    public function test_seeded_questions_are_visible_through_the_public_archive(): void
    {
        $this->getJson('/v1/public/question-archive/mcq')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertGreaterThan(
            0,
            count($this->getJson('/v1/public/question-archive/mcq')->json('result.data')),
        );

        $this->getJson('/v1/public/model-tests')
            ->assertOk();
    }

    public function test_reseeding_adds_nothing(): void
    {
        $counts = [
            Student::count(),
            Question::count(),
            ModelTest::count(),
            TestAttempt::count(),
            PracticeSession::count(),
        ];

        $this->seed(DemoExamPrepSeeder::class);

        $this->assertSame($counts, [
            Student::count(),
            Question::count(),
            ModelTest::count(),
            TestAttempt::count(),
            PracticeSession::count(),
        ]);
    }
}
