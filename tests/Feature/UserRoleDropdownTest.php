<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleDropdownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('staff', 'web');
        Role::findOrCreate('employee', 'web');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    public function test_a_user_can_be_created_without_a_role(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/users', $this->payload())
            ->assertCreated()
            ->assertJsonPath('result.roles', []);

        $this->assertSame([], User::where('email', 'new@example.com')->sole()->getRoleNames()->all());
    }

    public function test_a_blank_role_is_treated_as_no_role(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/users', $this->payload(['role' => '']))
            ->assertCreated();

        $this->assertSame([], User::where('email', 'new@example.com')->sole()->getRoleNames()->all());
    }

    public function test_the_picked_role_is_assigned(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/users', $this->payload(['role' => 'staff']))
            ->assertCreated()
            ->assertJsonPath('result.roles', ['staff']);
    }

    public function test_an_unknown_role_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/users', $this->payload(['role' => 'wizard']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('role');
    }

    public function test_an_update_swaps_the_single_role(): void
    {
        $target = User::factory()->create();
        $target->syncRoles(['staff']);

        $this->actingAs(User::factory()->create())
            ->putJson("/v1/admin/users/{$target->id}", [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'employee',
            ])
            ->assertOk()
            ->assertJsonPath('result.roles', ['employee']);
    }

    public function test_an_update_with_a_blank_role_clears_it(): void
    {
        $target = User::factory()->create();
        $target->syncRoles(['staff']);

        $this->actingAs(User::factory()->create())
            ->putJson("/v1/admin/users/{$target->id}", [
                'name' => $target->name,
                'email' => $target->email,
                'role' => '',
            ])
            ->assertOk()
            ->assertJsonPath('result.roles', []);
    }
}
