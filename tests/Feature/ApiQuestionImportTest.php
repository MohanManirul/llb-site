<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ApiQuestionImportTest extends TestCase
{
    use RefreshDatabase;

    private Subject $subject;

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
    }

    private const string HEADER = 'subject,type,exam_stage,exam_year,question_bn,question_en,option_1,option_2,option_3,option_4,option_5,correct_option,explanation_bn,explanation_en,reference';

    private function csvFile(array $rows): UploadedFile
    {
        $content = implode("\n", [self::HEADER, ...$rows]);

        return UploadedFile::fake()->createWithContent('questions.csv', $content);
    }

    public function test_the_template_downloads_as_csv(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get('/v1/admin/questions/import/template');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('question_bn', $response->getContent());
    }

    public function test_a_valid_csv_imports_all_rows_as_drafts(): void
    {
        $file = $this->csvFile([
            'penal-code,mcq,mcq,2023,প্রশ্ন এক?,,ক,খ,গ,ঘ,,2,ব্যাখ্যা,,Penal Code',
            $this->subject->id.',written,written,2022,প্রশ্ন দুই?,,,,,,,,,,',
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/questions/import', ['file' => $file])
            ->assertOk()
            ->assertJsonPath('result.imported', 2)
            ->assertJsonPath('result.failed', 0);

        $this->assertDatabaseCount('questions', 2);
        $this->assertDatabaseCount('question_options', 4);
        $this->assertDatabaseHas('questions', ['question_bn' => 'প্রশ্ন এক?', 'status' => 'draft']);
        $this->assertDatabaseHas('question_options', ['option_bn' => 'খ', 'is_correct' => true]);
    }

    public function test_one_bad_row_blocks_the_whole_import_with_row_errors(): void
    {
        $file = $this->csvFile([
            'penal-code,mcq,mcq,2023,প্রশ্ন এক?,,ক,খ,গ,ঘ,,2,,,',
            'unknown-subject,mcq,mcq,2023,প্রশ্ন দুই?,,ক,খ,,,,1,,,',
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/questions/import', ['file' => $file])
            ->assertOk()
            ->assertJsonPath('result.imported', 0)
            ->assertJsonPath('result.failed', 1)
            ->assertJsonPath('result.errors.0.row', 3);

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_a_wrong_header_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('questions.csv', "foo,bar\n1,2");

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/questions/import', ['file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_an_mcq_row_without_correct_option_fails(): void
    {
        $file = $this->csvFile([
            'penal-code,mcq,mcq,2023,প্রশ্ন?,,ক,খ,গ,ঘ,,,,,',
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/questions/import', ['file' => $file])
            ->assertOk()
            ->assertJsonPath('result.imported', 0)
            ->assertJsonPath('result.failed', 1);
    }
}
