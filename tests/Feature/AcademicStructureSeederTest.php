<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\Subject;
use Database\Seeders\AcademicStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicStructureSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_five_programs_with_their_level_shapes(): void
    {
        $this->seed(AcademicStructureSeeder::class);

        $this->assertSame(5, Program::count());

        $this->assertSame(2, Program::where('slug', 'nu-llb-pass')->first()->levels()->count());
        $this->assertSame(4, Program::where('slug', 'llb-hons')->first()->levels()->count());
        $this->assertSame(2, Program::where('slug', 'llm')->first()->levels()->count());
    }

    /**
     * Bar Council and BJS are stage-based programs: has_levels must be false
     * and neither may own a single program_levels row.
     */
    public function test_stage_based_programs_have_no_levels(): void
    {
        $this->seed(AcademicStructureSeeder::class);

        foreach (['bar-council', 'bjs'] as $slug) {
            $program = Program::where('slug', $slug)->first();

            $this->assertFalse($program->has_levels);
            $this->assertTrue($program->has_exam_stages);
            $this->assertSame(0, $program->levels()->count());
        }
    }

    public function test_bar_council_subjects_are_level_less(): void
    {
        $this->seed(AcademicStructureSeeder::class);

        $program = Program::where('slug', 'bar-council')->first();

        $this->assertGreaterThan(0, $program->subjects()->count());
        $this->assertSame(0, $program->subjects()->whereNotNull('program_level_id')->count());
    }

    public function test_reseeding_is_idempotent(): void
    {
        $this->seed(AcademicStructureSeeder::class);

        $counts = [
            Program::count(),
            ProgramLevel::count(),
            AcademicSession::count(),
            Subject::count(),
        ];

        $this->seed(AcademicStructureSeeder::class);

        $this->assertSame($counts, [
            Program::count(),
            ProgramLevel::count(),
            AcademicSession::count(),
            Subject::count(),
        ]);
    }

    public function test_exactly_one_session_is_current(): void
    {
        $this->seed(AcademicStructureSeeder::class);

        $this->assertSame(1, AcademicSession::where('is_current', true)->count());
    }

    /**
     * The same subject name exists under NU LLB (Pass) 2nd Part and under Bar
     * Council; the second occurrence must get a program-suffixed slug instead
     * of colliding on the global unique index.
     */
    public function test_cross_program_subject_slugs_do_not_collide(): void
    {
        $this->seed(AcademicStructureSeeder::class);

        $slugs = Subject::where('name_en', 'Code of Civil Procedure')->pluck('slug');

        $this->assertCount(2, $slugs);
        $this->assertSame($slugs->count(), $slugs->unique()->count());
    }
}
