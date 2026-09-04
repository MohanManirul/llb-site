<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Question;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPracticeTest extends TestCase
{
    use RefreshDatabase;

    private Subject $subject;

    private Student $student;

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
    }

    private function makeMcq(string $status = 'published'): Question
    {
        $question = Question::create([
            'type' => 'mcq',
            'subject_id' => $this->subject->id,
            'question_bn' => 'প্রশ্ন '.uniqid(),
            'explanation_bn' => 'ব্যাখ্যা',
            'status' => $status,
        ]);

        $question->options()->createMany([
            ['option_bn' => 'ক', 'is_correct' => true, 'sort_order' => 1],
            ['option_bn' => 'খ', 'is_correct' => false, 'sort_order' => 2],
        ]);

        return $question;
    }

    public function test_practice_requires_a_student_login(): void
    {
        $this->getJson('/v1/student/practice/questions?subject_id='.$this->subject->id)
            ->assertUnauthorized();
    }

    public function test_practice_questions_include_answers_and_explanations(): void
    {
        $this->makeMcq();
        $this->makeMcq('draft');

        $response = $this->actingAs($this->student, 'student')
            ->getJson('/v1/student/practice/questions?subject_id='.$this->subject->id.'&count=10')
            ->assertOk()
            ->assertJsonCount(1, 'result.data');

        $options = collect($response->json('result.data.0.options'));

        $this->assertTrue($options->contains(fn ($option) => $option['is_correct'] === true));
        $this->assertSame('ব্যাখ্যা', $response->json('result.data.0.explanation.bn'));
    }

    public function test_the_count_parameter_caps_the_question_set(): void
    {
        foreach (range(1, 5) as $ignored) {
            $this->makeMcq();
        }

        $this->actingAs($this->student, 'student')
            ->getJson('/v1/student/practice/questions?subject_id='.$this->subject->id.'&count=3')
            ->assertOk()
            ->assertJsonCount(3, 'result.data');
    }

    public function test_practice_subjects_list_only_subjects_with_published_mcqs(): void
    {
        $this->makeMcq();

        Subject::create([
            'program_id' => $this->subject->program_id,
            'slug' => 'empty-subject',
            'name_bn' => 'ফাঁকা বিষয়',
            'name_en' => 'Empty Subject',
        ]);

        $this->actingAs($this->student, 'student')
            ->getJson('/v1/student/practice/subjects')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.question_count', 1);
    }

    public function test_a_practice_session_is_recorded_and_listed(): void
    {
        $this->actingAs($this->student, 'student')
            ->postJson('/v1/student/practice/sessions', [
                'subject_id' => $this->subject->id,
                'question_count' => 10,
                'correct_count' => 7,
            ])
            ->assertCreated()
            ->assertJsonPath('result.correct_count', 7);

        $this->actingAs($this->student, 'student')
            ->getJson('/v1/student/practice/sessions')
            ->assertOk()
            ->assertJsonCount(1, 'result.data');
    }

    public function test_correct_count_cannot_exceed_question_count(): void
    {
        $this->actingAs($this->student, 'student')
            ->postJson('/v1/student/practice/sessions', [
                'subject_id' => $this->subject->id,
                'question_count' => 10,
                'correct_count' => 12,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['correct_count']);
    }
}
