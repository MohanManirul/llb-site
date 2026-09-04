<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `users.phone` is optional: a phone number describes the person, not the job,
 * so it lives here rather than on `employees` — and nothing may require it.
 */
class UserPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_be_created_without_a_phone(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/users', [
                'name' => 'No Phone',
                'email' => 'nophone@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertCreated()
            ->assertJsonPath('result.phone', null);

        $this->assertDatabaseHas('users', [
            'email' => 'nophone@example.com',
            'phone' => null,
        ]);
    }

    /** The form always sends every field, so a blank box arrives as "". */
    public function test_an_empty_string_phone_is_accepted(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/users', [
                'name' => 'Blank Phone',
                'email' => 'blank@example.com',
                'phone' => '',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertCreated();

        $this->assertNotNull(User::firstWhere('email', 'blank@example.com'));
    }

    public function test_a_phone_persists_and_comes_back_on_the_resource(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/users', [
                'name' => 'Has Phone',
                'email' => 'hasphone@example.com',
                'phone' => '01711111111',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertCreated()
            ->assertJsonPath('result.phone', '01711111111');

        $user = User::firstWhere('email', 'hasphone@example.com');
        $this->assertSame('01711111111', $user->phone);

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('result.phone', '01711111111');
    }

    public function test_an_update_can_set_and_clear_the_phone(): void
    {
        $user = User::factory()->create(['phone' => null]);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->putJson("/v1/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '01822222222',
            ])
            ->assertOk()
            ->assertJsonPath('result.phone', '01822222222');

        $this->actingAs($admin)
            ->putJson("/v1/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '',
            ])
            ->assertOk();

        $this->assertNull($user->fresh()->phone);
    }

    /** The users list is searched by phone as well as name and email. */
    public function test_the_users_list_can_be_searched_by_phone(): void
    {
        $match = User::factory()->create(['name' => 'Dialled In', 'phone' => '01711111111']);
        User::factory()->create(['name' => 'Other Line', 'phone' => '01822222222']);

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/users?search=017111')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.id', $match->id);
    }

    /** Two staff sharing an office line must not be a validation error. */
    public function test_the_phone_is_not_unique(): void
    {
        User::factory()->create(['phone' => '01711111111']);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/users', [
                'name' => 'Shared Line',
                'email' => 'shared@example.com',
                'phone' => '01711111111',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertCreated();
    }
}
