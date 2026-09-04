<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiStudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_are_listed_with_search_and_active_filter(): void
    {
        Student::create(['name' => 'Rahim Uddin', 'email' => 'rahim@example.com', 'password' => 'password-123']);
        Student::create(['name' => 'Karim Mia', 'email' => 'karim@example.com', 'password' => 'password-123', 'is_active' => false]);

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->getJson('/v1/admin/students')
            ->assertOk()
            ->assertJsonCount(2, 'result.data');

        $this->actingAs($admin)
            ->getJson('/v1/admin/students?search=rahim')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Rahim Uddin');

        $this->actingAs($admin)
            ->getJson('/v1/admin/students?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.email', 'karim@example.com');
    }

    public function test_toggle_active_deactivates_and_blocks_the_student_session(): void
    {
        $student = Student::create([
            'name' => 'K', 'email' => 'karim@example.com', 'password' => 'secret-password',
        ]);

        $this->actingAs(User::factory()->create())
            ->patchJson("/v1/admin/students/{$student->id}/active")
            ->assertOk()
            ->assertJsonPath('result.is_active', false);

        $this->actingAs($student->fresh(), 'student')
            ->getJson('/v1/student/auth/me')
            ->assertForbidden();
    }

    public function test_staff_role_cannot_toggle_students(): void
    {
        $this->seed(UserSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(UserSeeder::STAFF);

        $student = Student::create([
            'name' => 'K', 'email' => 'karim@example.com', 'password' => 'secret-password',
        ]);

        $this->actingAs($staff)
            ->patchJson("/v1/admin/students/{$student->id}/active")
            ->assertForbidden();

        $this->actingAs($staff)
            ->getJson('/v1/admin/students')
            ->assertOk();
    }
}
