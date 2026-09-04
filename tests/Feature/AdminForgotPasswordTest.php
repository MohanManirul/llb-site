<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AdminResetPassword;
use App\Services\Auth\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Forgot-password for the super admin: request a link, follow it, set a new
 * password. An address with no super-admin account behind it is rejected with
 * a validation error so the user knows to check what they typed.
 */
class AdminForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super-admin', 'web');
        Role::findOrCreate('staff', 'web');
    }

    private function superAdmin(string $email = 'admin@example.com'): User
    {
        $user = User::factory()->create(['email' => $email, 'password' => 'old-password']);
        $user->syncRoles('super-admin');

        return $user;
    }

    public function test_the_login_page_links_to_forgot_password(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/login/page'));

        $this->get('/admin/forgot-password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/forgot-password/page'));
    }

    public function test_a_super_admin_receives_a_reset_link(): void
    {
        Notification::fake();

        $user = $this->superAdmin();

        $this->post('/admin/forgot-password', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('success', PasswordResetService::LINK_SENT_MESSAGE);

        Notification::assertSentTo($user, AdminResetPassword::class);

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    /**
     * The token is written before the job is dispatched, so the link is valid
     * the moment the request returns — only the mail waits for a worker.
     */
    public function test_the_mail_is_queued_rather_than_sent_in_the_request(): void
    {
        Queue::fake();

        $user = $this->superAdmin();

        $this->post('/admin/forgot-password', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('success', PasswordResetService::LINK_SENT_MESSAGE);

        Queue::assertPushed(SendQueuedNotifications::class, function ($job) {
            return $job->notification instanceof AdminResetPassword
                && $job->notification->queue === null;
        });

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_an_unknown_address_is_told_no_account_exists(): void
    {
        Notification::fake();

        $this->post('/admin/forgot-password', ['email' => 'nobody@example.com'])
            ->assertRedirect()
            ->assertSessionHasErrors(['email' => PasswordResetService::ACCOUNT_NOT_FOUND_MESSAGE]);

        Notification::assertNothingSent();

        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_a_non_super_admin_is_told_no_account_exists(): void
    {
        Notification::fake();

        $staff = User::factory()->create(['email' => 'staff@example.com']);
        $staff->syncRoles('staff');

        $this->post('/admin/forgot-password', ['email' => $staff->email])
            ->assertRedirect()
            ->assertSessionHasErrors(['email' => PasswordResetService::ACCOUNT_NOT_FOUND_MESSAGE]);

        Notification::assertNothingSent();

        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_the_reset_page_renders_with_the_token(): void
    {
        $user = $this->superAdmin();
        $token = Password::createToken($user);

        $this->get("/admin/reset-password/{$token}?email=".urlencode($user->email))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/reset-password/page')
                ->where('token', $token)
                ->where('email', $user->email)
            );
    }

    public function test_a_super_admin_can_reset_and_sign_in_with_the_new_password(): void
    {
        $user = $this->superAdmin();
        $token = Password::createToken($user);

        $this->post('/admin/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertRedirect('/admin/login')
            ->assertSessionHas('success', PasswordResetService::RESET_MESSAGE);

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));

        $this->assertDatabaseCount('password_reset_tokens', 0);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'new-password',
        ])->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_used_token_cannot_be_used_again(): void
    {
        $user = $this->superAdmin();
        $token = Password::createToken($user);

        $payload = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $this->post('/admin/reset-password', $payload)->assertRedirect('/admin/login');

        $this->post('/admin/reset-password', array_merge($payload, [
            'password' => 'second-password',
            'password_confirmation' => 'second-password',
        ]))->assertSessionHasErrors(['email' => PasswordResetService::INVALID_TOKEN_MESSAGE]);

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $user = $this->superAdmin();
        $token = Password::createToken($user);

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subMinutes(config('auth.passwords.users.expire') + 5)]);

        $this->post('/admin/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors(['email' => PasswordResetService::INVALID_TOKEN_MESSAGE]);

        $user->refresh();
        $this->assertTrue(Hash::check('old-password', $user->password));
    }

    public function test_a_forged_token_is_rejected(): void
    {
        $user = $this->superAdmin();
        Password::createToken($user);

        $this->post('/admin/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors(['email' => PasswordResetService::INVALID_TOKEN_MESSAGE]);

        $user->refresh();
        $this->assertTrue(Hash::check('old-password', $user->password));
    }

    public function test_a_non_super_admin_cannot_reset_even_with_a_token(): void
    {
        $staff = User::factory()->create(['email' => 'staff@example.com', 'password' => 'old-password']);
        $staff->syncRoles('staff');

        $token = Password::createToken($staff);

        $this->post('/admin/reset-password', [
            'token' => $token,
            'email' => $staff->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors(['email' => PasswordResetService::INVALID_TOKEN_MESSAGE]);

        $staff->refresh();
        $this->assertTrue(Hash::check('old-password', $staff->password));
    }

    public function test_the_new_password_must_be_confirmed_and_long_enough(): void
    {
        $user = $this->superAdmin();
        $token = Password::createToken($user);

        $this->post('/admin/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->post('/admin/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertTrue(Hash::check('old-password', $user->password));
    }

    public function test_the_request_form_is_rate_limited(): void
    {
        $user = $this->superAdmin();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/admin/forgot-password', ['email' => $user->email])->assertRedirect();
        }

        $this->post('/admin/forgot-password', ['email' => $user->email])
            ->assertStatus(429);
    }
}
