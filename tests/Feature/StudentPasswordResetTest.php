<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Notifications\StudentResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class StudentPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reset_link_is_sent_to_an_existing_student(): void
    {
        Notification::fake();

        $student = Student::create([
            'name' => 'K', 'email' => 'karim@example.com', 'password' => 'secret-password',
        ]);

        $this->postJson('/v1/student/auth/forgot-password', ['email' => 'karim@example.com'])
            ->assertOk();

        Notification::assertSentTo($student, StudentResetPassword::class);

        $this->assertDatabaseHas('student_password_reset_tokens', ['email' => 'karim@example.com']);
    }

    public function test_an_unknown_email_gets_the_same_response_without_a_notification(): void
    {
        Notification::fake();

        $this->postJson('/v1/student/auth/forgot-password', ['email' => 'nobody@example.com'])
            ->assertOk();

        Notification::assertNothingSent();
    }

    public function test_a_staff_email_does_not_receive_a_student_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'staff@example.com']);

        $this->postJson('/v1/student/auth/forgot-password', ['email' => 'staff@example.com'])
            ->assertOk();

        Notification::assertNothingSent();

        $this->assertDatabaseMissing('student_password_reset_tokens', ['email' => $user->email]);
    }

    public function test_a_valid_token_resets_the_password(): void
    {
        $student = Student::create([
            'name' => 'K', 'email' => 'karim@example.com', 'password' => 'old-password-1',
        ]);

        $token = Password::broker('students')->createToken($student);

        $this->postJson('/v1/student/auth/reset-password', [
            'token' => $token,
            'email' => 'karim@example.com',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password-1', $student->fresh()->password));
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        Student::create([
            'name' => 'K', 'email' => 'karim@example.com', 'password' => 'old-password-1',
        ]);

        $this->postJson('/v1/student/auth/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'karim@example.com',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertStatus(422);
    }
}
