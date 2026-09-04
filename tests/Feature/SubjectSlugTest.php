<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\SubjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectSlugTest extends TestCase
{
    use RefreshDatabase;

    private Program $hons;

    private Program $barCouncil;

    private ProgramLevel $firstYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hons = Program::create([
            'slug' => 'llb-hons', 'name_bn' => 'এলএলবি (অনার্স)', 'name_en' => 'LLB (Hons)',
            'has_levels' => true, 'level_label_bn' => 'বর্ষ', 'level_label_en' => 'Year',
        ]);

        $this->barCouncil = Program::create([
            'slug' => 'bar-council', 'name_bn' => 'বার কাউন্সিল', 'name_en' => 'Bar Council',
            'has_levels' => false, 'has_exam_stages' => true, 'has_sessions' => false,
        ]);

        $this->firstYear = ProgramLevel::create([
            'program_id' => $this->hons->id, 'position' => 1, 'slug' => '1st-year',
            'name_bn' => '১ম বর্ষ', 'name_en' => '1st Year',
        ]);
    }

    /**
     * Str::ascii transliterates Bangla (via portable-ascii), so a Bangla-only
     * name still yields a readable ASCII slug; the random fallback only fires
     * for strings that transliterate to nothing.
     */
    public function test_a_bangla_only_title_gets_an_ascii_slug(): void
    {
        $subject = app(SubjectService::class)->create([
            'program_id' => $this->hons->id,
            'program_level_id' => $this->firstYear->id,
            'name_bn' => 'সাক্ষ্য আইন',
            'name_en' => 'সাক্ষ্য আইন',
        ]);

        $this->assertNotSame('', $subject->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $subject->slug);
    }

    public function test_the_same_name_under_another_program_gets_a_program_suffixed_slug(): void
    {
        $service = app(SubjectService::class);

        $first = $service->create([
            'program_id' => $this->hons->id,
            'program_level_id' => $this->firstYear->id,
            'name_bn' => 'সাক্ষ্য আইন',
            'name_en' => 'Law of Evidence',
        ]);

        $second = $service->create([
            'program_id' => $this->barCouncil->id,
            'program_level_id' => null,
            'name_bn' => 'সাক্ষ্য আইন',
            'name_en' => 'Law of Evidence',
        ]);

        $this->assertSame('law-of-evidence', $first->slug);
        $this->assertSame('law-of-evidence-bar-council', $second->slug);
    }

    /**
     * NULLs are distinct in a unique index on both Postgres and SQLite, so the
     * DB cannot reject a duplicate level-less subject. The FormRequest's
     * whereNull branch is the only guard — this test proves it fires.
     */
    public function test_a_duplicate_level_less_subject_is_rejected_by_validation(): void
    {
        Subject::create([
            'program_id' => $this->barCouncil->id,
            'program_level_id' => null,
            'slug' => 'penal-code',
            'name_bn' => 'দণ্ডবিধি',
            'name_en' => 'Penal Code',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/subjects', [
                'program_id' => $this->barCouncil->id,
                'program_level_id' => null,
                'name_bn' => 'দণ্ডবিধি',
                'name_en' => 'Penal Code',
                'is_active' => true,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name_bn', 'name_en']);
    }

    public function test_a_duplicate_under_the_same_program_and_level_is_rejected(): void
    {
        Subject::create([
            'program_id' => $this->hons->id,
            'program_level_id' => $this->firstYear->id,
            'slug' => 'jurisprudence',
            'name_bn' => 'আইনতত্ত্ব',
            'name_en' => 'Jurisprudence',
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/subjects', [
                'program_id' => $this->hons->id,
                'program_level_id' => $this->firstYear->id,
                'name_bn' => 'আইনতত্ত্ব',
                'name_en' => 'Jurisprudence',
                'is_active' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name_bn']);
    }

    public function test_the_same_name_under_a_different_level_is_accepted(): void
    {
        $secondYear = ProgramLevel::create([
            'program_id' => $this->hons->id, 'position' => 2, 'slug' => '2nd-year',
            'name_bn' => '২য় বর্ষ', 'name_en' => '2nd Year',
        ]);

        Subject::create([
            'program_id' => $this->hons->id,
            'program_level_id' => $this->firstYear->id,
            'slug' => 'english',
            'name_bn' => 'ইংরেজি',
            'name_en' => 'English',
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/subjects', [
                'program_id' => $this->hons->id,
                'program_level_id' => $secondYear->id,
                'name_bn' => 'ইংরেজি',
                'name_en' => 'English',
                'is_active' => true,
            ])
            ->assertStatus(201);
    }

    public function test_a_level_from_another_program_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/subjects', [
                'program_id' => $this->barCouncil->id,
                'program_level_id' => $this->firstYear->id,
                'name_bn' => 'দণ্ডবিধি',
                'name_en' => 'Penal Code',
                'is_active' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['program_level_id']);
    }
}
