<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicQuestionArchiveTest extends TestCase
{
    use RefreshDatabase;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $program = Program::create([
            'slug' => 'bjs', 'name_bn' => 'বিজেএস', 'name_en' => 'BJS',
            'has_levels' => false, 'has_exam_stages' => true, 'has_sessions' => false,
        ]);

        $this->subject = Subject::create([
            'program_id' => $program->id,
            'slug' => 'general-bengali',
            'name_bn' => 'সাধারণ বাংলা',
            'name_en' => 'General Bengali',
        ]);
    }

    private function makeMcq(string $status = 'published', ?int $year = 2023): Question
    {
        $question = Question::create([
            'type' => 'mcq',
            'subject_id' => $this->subject->id,
            'exam_stage' => 'preliminary',
            'exam_year' => $year,
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

    public function test_the_mcq_archive_lists_published_questions_with_answers_inline(): void
    {
        $this->makeMcq();
        $this->makeMcq('draft');

        $this->getJson('/v1/public/question-archive/mcq')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.options.0.is_correct', true)
            ->assertJsonPath('result.data.0.explanation.bn', 'ব্যাখ্যা');
    }

    public function test_the_mcq_archive_only_lists_year_tagged_questions(): void
    {
        $this->makeMcq();
        $this->makeMcq('published', null);

        $this->getJson('/v1/public/question-archive/mcq')
            ->assertOk()
            ->assertJsonCount(1, 'result.data');
    }

    public function test_the_written_archive_lists_published_written_questions(): void
    {
        Question::create([
            'type' => 'written',
            'subject_id' => $this->subject->id,
            'exam_stage' => 'written',
            'exam_year' => 2022,
            'question_bn' => 'বাংলা ভাষার উদ্ভব আলোচনা করুন।',
            'status' => 'published',
        ]);

        $this->makeMcq();

        $this->getJson('/v1/public/question-archive/written')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.type', 'written');
    }

    public function test_filters_by_subject_year_and_stage(): void
    {
        $this->makeMcq();
        $this->makeMcq('published', 2021);

        $this->getJson('/v1/public/question-archive/mcq?exam_year=2021')
            ->assertOk()
            ->assertJsonCount(1, 'result.data');

        $this->getJson('/v1/public/question-archive/mcq?subject=general-bengali')
            ->assertOk()
            ->assertJsonCount(2, 'result.data');

        $this->getJson('/v1/public/question-archive/mcq?subject=unknown-subject')
            ->assertOk()
            ->assertJsonCount(0, 'result.data');
    }

    public function test_the_filters_endpoint_lists_programs_subjects_and_years(): void
    {
        $this->makeMcq();

        $response = $this->getJson('/v1/public/question-archive/filters')->assertOk();

        $keys = collect($response->json('result'))->pluck('key');

        $this->assertSame(['program', 'subject', 'exam_stage', 'exam_year'], $keys->all());
    }
}
