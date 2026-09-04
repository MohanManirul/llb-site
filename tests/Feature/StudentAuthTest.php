<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAuthTest extends TestCase
{
    use RefreshDatabase;

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rahim Uddin',
            'email' => 'rahim@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ], $overrides);
    }

    public function test_a_student_can_register_and_is_logged_in(): void
    {
        $response = $this->postJson('/v1/student/auth/register', $this->registerPayload());

        $response->assertCreated()->assertJsonPath('result.email', 'rahim@example.com');

        $this->assertDatabaseHas('students', ['email' => 'rahim@example.com']);

        $this->getJson('/v1/student/auth/me')
            ->assertOk()
            ->assertJsonPath('result.email', 'rahim@example.com');
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        Student::create(['name' => 'A', 'email' => 'rahim@example.com', 'password' => 'password-123']);

        $this->postJson('/v1/student/auth/register', $this->registerPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_a_student_can_login_and_logout(): void
    {
        $student = Student::create([
            'name' => 'Karim', 'email' => 'karim@example.com', 'password' => 'secret-password',
        ]);

        $this->postJson('/v1/student/auth/login', [
            'email' => 'karim@example.com',
            'password' => 'secret-password',
        ])->assertOk()->assertJsonPath('result.id', $student->id);

        $this->assertNotNull($student->fresh()->last_login_at);

        $this->postJson('/v1/student/auth/logout')->assertOk();

        $this->getJson('/v1/student/auth/me')->assertUnauthorized();
    }

    public function test_login_fails_with_a_wrong_password(): void
    {
        Student::create(['name' => 'K', 'email' => 'karim@example.com', 'password' => 'secret-password']);

        $this->postJson('/v1/student/auth/login', [
            'email' => 'karim@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_a_deactivated_student_cannot_login(): void
    {
        Student::create([
            'name' => 'K', 'email' => 'karim@example.com',
            'password' => 'secret-password', 'is_active' => false,
        ]);

        $this->postJson('/v1/student/auth/login', [
            'email' => 'karim@example.com',
            'password' => 'secret-password',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_a_deactivated_student_session_is_rejected(): void
    {
        $student = Student::create([
            'name' => 'K', 'email' => 'karim@example.com', 'password' => 'secret-password',
        ]);

        $student->update(['is_active' => false]);

        $this->actingAs($student, 'student')
            ->getJson('/v1/student/auth/me')
            ->assertForbidden();
    }

    public function test_guest_requests_to_student_endpoints_return_401_json(): void
    {
        $this->getJson('/v1/student/auth/me')->assertUnauthorized();
        $this->getJson('/v1/student/practice/subjects')->assertUnauthorized();
        $this->getJson('/v1/student/model-tests')->assertUnauthorized();
    }

    public function test_a_student_can_update_their_profile(): void
    {
        $student = Student::create([
            'name' => 'K', 'email' => 'karim@example.com', 'password' => 'secret-password',
        ]);

        $this->actingAs($student, 'student')
            ->patchJson('/v1/student/auth/profile', [
                'name' => 'Karim Mia',
                'phone' => '01700000000',
            ])
            ->assertOk()
            ->assertJsonPath('result.name', 'Karim Mia')
            ->assertJsonPath('result.phone', '01700000000');
    }

    public function test_student_auth_does_not_grant_staff_api_access(): void
    {
        $student = Student::create([
            'name' => 'K', 'email' => 'karim@example.com', 'password' => 'secret-password',
        ]);

        $this->actingAs($student, 'student')
            ->getJson('/v1/admin/dashboard')
            ->assertUnauthorized();
    }

    public function test_staff_login_does_not_grant_student_api_access(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/v1/student/auth/me')
            ->assertUnauthorized();
    }

    public function test_guest_student_pages_redirect_authenticated_students_to_exam_prep(): void
    {
        $student = Student::create([
            'name' => 'K', 'email' => 'karim@example.com', 'password' => 'secret-password',
        ]);

        $this->actingAs($student, 'student')
            ->get('/bn/account/login')
            ->assertRedirect('/bn/exam-prep');
    }

    public function test_protected_student_pages_redirect_guests_to_the_locale_login(): void
    {
        $this->get('/bn/practice')
            ->assertRedirect('/bn/account/login?redirect='.urlencode('/bn/practice'));
    }
}
