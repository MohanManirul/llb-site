<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAuthTest extends TestCase
{
    use RefreshDatabase;

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Professor Karim',
            'email' => 'karim@college.test',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'college_id' => College::factory()->create()->id,
        ], $overrides);
    }

    public function test_registration_creates_an_inactive_account_and_does_not_log_in(): void
    {
        $this->postJson('/v1/teacher/auth/register', $this->registerPayload())
            ->assertCreated()
            ->assertJsonPath('result.is_active', false);

        $this->assertDatabaseHas('teachers', ['email' => 'karim@college.test', 'is_active' => false]);

        $this->getJson('/v1/teacher/auth/me')->assertUnauthorized();
    }

    public function test_registration_requires_an_active_college(): void
    {
        $inactive = College::factory()->inactive()->create();

        $this->postJson('/v1/teacher/auth/register', $this->registerPayload(['college_id' => $inactive->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['college_id']);
    }

    public function test_a_pending_teacher_cannot_log_in(): void
    {
        Teacher::factory()->create([
            'email' => 'pending@college.test',
            'password' => 'secret-password',
        ]);

        $this->postJson('/v1/teacher/auth/login', [
            'email' => 'pending@college.test',
            'password' => 'secret-password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest('teacher');
    }

    public function test_an_approved_teacher_can_log_in_and_out(): void
    {
        $teacher = Teacher::factory()->active()->create([
            'email' => 'approved@college.test',
            'password' => 'secret-password',
        ]);

        $this->postJson('/v1/teacher/auth/login', [
            'email' => 'approved@college.test',
            'password' => 'secret-password',
        ])
            ->assertOk()
            ->assertJsonPath('result.id', $teacher->id);

        $this->assertNotNull($teacher->fresh()->last_login_at);

        $this->postJson('/v1/teacher/auth/logout')->assertOk();
    }

    public function test_admin_approval_flips_the_account_and_unblocks_login(): void
    {
        $teacher = Teacher::factory()->create([
            'email' => 'waiting@college.test',
            'password' => 'secret-password',
        ]);

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/v1/admin/teachers/{$teacher->id}/active")
            ->assertOk()
            ->assertJsonPath('result.is_active', true);

        $fresh = $teacher->fresh();
        $this->assertNotNull($fresh->approved_at);
        $this->assertSame($admin->id, $fresh->approved_by);

        $this->postJson('/v1/teacher/auth/login', [
            'email' => 'waiting@college.test',
            'password' => 'secret-password',
        ])->assertOk();
    }

    public function test_a_deactivated_teacher_session_is_blocked(): void
    {
        $teacher = Teacher::factory()->active()->create();

        $this->actingAs(User::factory()->create())
            ->patchJson("/v1/admin/teachers/{$teacher->id}/active")
            ->assertOk()
            ->assertJsonPath('result.is_active', false);

        $this->actingAs($teacher->fresh(), 'teacher')
            ->getJson('/v1/teacher/auth/me')
            ->assertForbidden();
    }

    public function test_teachers_are_listed_for_admins_with_a_college_filter(): void
    {
        $collegeA = College::factory()->create();
        $collegeB = College::factory()->create();

        Teacher::factory()->create(['name' => 'Teacher A', 'college_id' => $collegeA->id]);
        Teacher::factory()->active()->create(['name' => 'Teacher B', 'college_id' => $collegeB->id]);

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->getJson('/v1/admin/teachers')
            ->assertOk()
            ->assertJsonCount(2, 'result.data');

        $this->actingAs($admin)
            ->getJson("/v1/admin/teachers?college_id={$collegeA->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Teacher A');

        $this->actingAs($admin)
            ->getJson('/v1/admin/teachers?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Teacher A');
    }
}
