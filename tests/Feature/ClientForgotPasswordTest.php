<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Notifications\ClientResetPassword;
use App\Services\Auth\ClientPasswordResetService;
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
 * Forgot-password for the client portal on the base URL. Clients run on their
 * own broker and their own token table, so a link issued for one side of the
 * app can never reset an account on the other. An address with no portal-enabled
 * client behind it is rejected with a validation error so the user knows to
 * check what they typed.
 */
class ClientForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function client(array $attributes = []): Client
    {
        return Client::create(array_merge([
            'name' => 'Acme Client',
            'email' => 'client@example.com',
            'phone' => '01700000001',
            'password' => 'old-password',
            'is_active' => true,
        ], $attributes));
    }

    public function test_the_portal_login_and_forgot_pages_render(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('client/login/page'));

        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('client/forgot-password/page'));
    }

    public function test_an_active_client_receives_a_reset_link_pointing_at_the_portal(): void
    {
        Notification::fake();

        $client = $this->client();

        $this->post('/forgot-password', ['email' => $client->email])
            ->assertRedirect()
            ->assertSessionHas('success', ClientPasswordResetService::LINK_SENT_MESSAGE);

        Notification::assertSentTo($client, ClientResetPassword::class);

        $this->assertDatabaseHas('client_password_reset_tokens', ['email' => $client->email]);
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_the_mail_is_queued_rather_than_sent_in_the_request(): void
    {
        Queue::fake();

        $client = $this->client();

        $this->post('/forgot-password', ['email' => $client->email])
            ->assertRedirect()
            ->assertSessionHas('success', ClientPasswordResetService::LINK_SENT_MESSAGE);

        Queue::assertPushed(SendQueuedNotifications::class, function ($job) {
            return $job->notification instanceof ClientResetPassword
                && $job->notification->queue === null;
        });

        $this->assertDatabaseHas('client_password_reset_tokens', ['email' => $client->email]);
    }

    public function test_the_mailed_link_targets_the_client_reset_route(): void
    {
        $client = $this->client();

        $mail = (new ClientResetPassword('DUMMY-TOKEN'))->toMail($client);

        $this->assertStringContainsString('/reset-password/DUMMY-TOKEN', $mail->actionUrl);
        $this->assertStringNotContainsString('/admin/', $mail->actionUrl);
    }

    public function test_an_unknown_address_is_told_no_account_exists(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertRedirect()
            ->assertSessionHasErrors(['email' => ClientPasswordResetService::ACCOUNT_NOT_FOUND_MESSAGE]);

        Notification::assertNothingSent();
        $this->assertDatabaseCount('client_password_reset_tokens', 0);
    }

    public function test_an_inactive_client_is_told_no_account_exists(): void
    {
        Notification::fake();

        $client = $this->client(['is_active' => false]);

        $this->post('/forgot-password', ['email' => $client->email])
            ->assertRedirect()
            ->assertSessionHasErrors(['email' => ClientPasswordResetService::ACCOUNT_NOT_FOUND_MESSAGE]);

        Notification::assertNothingSent();
        $this->assertDatabaseCount('client_password_reset_tokens', 0);
    }

    public function test_the_reset_page_renders_with_the_token(): void
    {
        $client = $this->client();
        $token = Password::broker('clients')->createToken($client);

        $this->get("/reset-password/{$token}?email=".urlencode($client->email))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('client/reset-password/page')
                ->where('token', $token)
                ->where('email', $client->email)
            );
    }

    public function test_a_client_can_reset_and_sign_in_with_the_new_password(): void
    {
        $client = $this->client();
        $token = Password::broker('clients')->createToken($client);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $client->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertRedirect('/login')
            ->assertSessionHas('success', ClientPasswordResetService::RESET_MESSAGE);

        $client->refresh();
        $this->assertTrue(Hash::check('new-password', $client->password));
        $this->assertDatabaseCount('client_password_reset_tokens', 0);

        $this->post('/login', [
            'email' => $client->email,
            'password' => 'new-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($client, 'client-web');
    }

    public function test_a_used_token_cannot_be_used_again(): void
    {
        $client = $this->client();
        $token = Password::broker('clients')->createToken($client);

        $payload = [
            'token' => $token,
            'email' => $client->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $this->post('/reset-password', $payload)->assertRedirect('/login');

        $this->post('/reset-password', array_merge($payload, [
            'password' => 'second-password',
            'password_confirmation' => 'second-password',
        ]))->assertSessionHasErrors(['email' => ClientPasswordResetService::INVALID_TOKEN_MESSAGE]);

        $client->refresh();
        $this->assertTrue(Hash::check('new-password', $client->password));
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $client = $this->client();
        $token = Password::broker('clients')->createToken($client);

        DB::table('client_password_reset_tokens')
            ->where('email', $client->email)
            ->update(['created_at' => now()->subMinutes(config('auth.passwords.clients.expire') + 5)]);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $client->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors(['email' => ClientPasswordResetService::INVALID_TOKEN_MESSAGE]);

        $client->refresh();
        $this->assertTrue(Hash::check('old-password', $client->password));
    }

    public function test_a_forged_token_is_rejected(): void
    {
        $client = $this->client();
        Password::broker('clients')->createToken($client);

        $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $client->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors(['email' => ClientPasswordResetService::INVALID_TOKEN_MESSAGE]);

        $client->refresh();
        $this->assertTrue(Hash::check('old-password', $client->password));
    }

    /**
     * The same address can belong to a staff user and a client at once. Each
     * side's token must be worthless against the other's account.
     */
    public function test_an_admin_token_cannot_reset_a_client_account(): void
    {
        Role::findOrCreate('super-admin', 'web');

        $shared = 'shared@example.com';

        $admin = User::factory()->create(['email' => $shared, 'password' => 'admin-password']);
        $admin->syncRoles('super-admin');

        $client = $this->client(['email' => $shared, 'phone' => '01700000002']);

        $adminToken = Password::broker()->createToken($admin);

        $this->post('/reset-password', [
            'token' => $adminToken,
            'email' => $shared,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors(['email' => ClientPasswordResetService::INVALID_TOKEN_MESSAGE]);

        $client->refresh();
        $this->assertTrue(Hash::check('old-password', $client->password));

        $clientToken = Password::broker('clients')->createToken($client);

        $this->post('/admin/reset-password', [
            'token' => $clientToken,
            'email' => $shared,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('email');

        $admin->refresh();
        $this->assertTrue(Hash::check('admin-password', $admin->password));
    }

    public function test_the_new_password_must_be_confirmed_and_long_enough(): void
    {
        $client = $this->client();
        $token = Password::broker('clients')->createToken($client);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $client->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $client->email,
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');

        $client->refresh();
        $this->assertTrue(Hash::check('old-password', $client->password));
    }

    public function test_the_request_form_is_rate_limited(): void
    {
        $client = $this->client();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/forgot-password', ['email' => $client->email])->assertRedirect();
        }

        $this->post('/forgot-password', ['email' => $client->email])
            ->assertStatus(429);
    }
}
