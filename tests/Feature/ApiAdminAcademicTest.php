<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAdminAcademicTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/v1/admin/programs')->assertUnauthorized();
    }

    public function test_a_super_admin_can_create_a_program(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/programs', [
                'name_bn' => 'এলএলবি (অনার্স)',
                'name_en' => 'LLB (Hons)',
                'has_levels' => true,
                'level_label_bn' => 'বর্ষ',
                'level_label_en' => 'Year',
                'has_exam_stages' => false,
                'has_sessions' => true,
                'is_active' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('result.slug', 'llb-hons')
            ->assertJsonPath('result.name.bn', 'এলএলবি (অনার্স)')
            ->assertJsonPath('result.name.en', 'LLB (Hons)');
    }

    /**
     * TestCase::actingAs silently promotes a role-less user to super-admin, so
     * the staff role must be assigned explicitly for this to prove anything.
     */
    public function test_staff_cannot_touch_the_academic_structure(): void
    {
        $this->seed(UserSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(UserSeeder::STAFF);

        $this->actingAs($staff)
            ->postJson('/v1/admin/programs', ['name_bn' => 'x', 'name_en' => 'x'])
            ->assertForbidden();
    }

    public function test_staff_can_read_the_academic_structure(): void
    {
        $this->seed(UserSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(UserSeeder::STAFF);

        $this->actingAs($staff)->getJson('/v1/admin/programs')->assertOk();
    }

    public function test_program_options_carry_level_shapes(): void
    {
        $this->seed(AcademicStructureSeeder::class);

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/programs/options')
            ->assertOk();

        $programs = collect($response->json('result'))->keyBy('slug');

        $this->assertTrue($programs['llb-hons']['has_levels']);
        $this->assertCount(4, $programs['llb-hons']['levels']);
        $this->assertFalse($programs['bjs']['has_levels']);
        $this->assertTrue($programs['bjs']['has_exam_stages']);
        $this->assertCount(0, $programs['bjs']['levels']);
    }

    public function test_marking_a_session_current_clears_the_previous_one(): void
    {
        $first = AcademicSession::create([
            'slug' => '2024-25', 'label' => '2024-25',
            'start_year' => 2024, 'end_year' => 2025, 'is_current' => true,
        ]);

        $second = AcademicSession::create([
            'slug' => '2025-26', 'label' => '2025-26',
            'start_year' => 2025, 'end_year' => 2026,
        ]);

        $this->actingAs(User::factory()->create())
            ->patchJson("/v1/admin/academic-sessions/{$second->id}/current")
            ->assertOk()
            ->assertJsonPath('result.is_current', true);

        $this->assertFalse($first->refresh()->is_current);
        $this->assertSame(1, AcademicSession::where('is_current', true)->count());
    }

    public function test_deleting_a_program_that_still_has_subjects_returns_409(): void
    {
        $program = Program::create([
            'slug' => 'llb-hons', 'name_bn' => 'অনার্স', 'name_en' => 'LLB (Hons)',
        ]);

        Subject::create([
            'program_id' => $program->id,
            'slug' => 'jurisprudence',
            'name_bn' => 'আইনতত্ত্ব',
            'name_en' => 'Jurisprudence',
        ]);

        $this->actingAs(User::factory()->create())
            ->deleteJson("/v1/admin/programs/{$program->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('programs', ['id' => $program->id]);
    }
}
