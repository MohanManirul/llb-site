<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * GET /v1/admin/users/search backs the admin User pickers.
 *
 * Deliberately stricter than the neighbouring search endpoints: it returns
 * every account's name and email, which any logged-in user could otherwise
 * enumerate.
 */
class UserSearchEndpointTest extends TestCase
{
    use RefreshDatabase;

    /** A picker's disabled Phone preview needs `phone` on the option payload. */
    public function test_the_payload_carries_value_label_description_and_phone(): void
    {
        User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '01711111111',
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/users/search?search=Ada')
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.label', 'Ada Lovelace')
            ->assertJsonPath('result.0.description', 'ada@example.com')
            ->assertJsonPath('result.0.phone', '01711111111')
            ->assertJsonPath('result.0.image_url', null)
            ->assertJsonPath('result.0.thumbnail_url', null)
            ->assertJsonStructure(['result' => [['value', 'label', 'description', 'phone', 'image_url', 'thumbnail_url']]]);
    }

    public function test_it_searches_by_name_or_email(): void
    {
        User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
        User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->getJson('/v1/admin/users/search?search=grace@')
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.label', 'Grace Hopper');

        $this->actingAs($admin)
            ->getJson('/v1/admin/users/search?search=Lovelace')
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.label', 'Ada Lovelace');
    }

    public function test_it_caps_the_option_list_at_ten(): void
    {
        User::factory()->count(15)->create();

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/users/search')
            ->assertOk()
            ->assertJsonCount(10, 'result');
    }

    /**
     * TestCase::actingAs silently promotes a role-less user to super-admin, so
     * the gate has to be checked with an explicit role or it Passes for the
     * wrong reason.
     */
    public function test_a_role_without_the_permission_is_forbidden(): void
    {
        Permission::findOrCreate('view roles', 'web');
        $role = Role::findOrCreate('viewer', 'web');
        $role->givePermissionTo('view roles');

        $viewer = User::factory()->create();
        $viewer->assignRole($role);

        $this->actingAs($viewer)
            ->getJson('/v1/admin/users/search')
            ->assertForbidden();
    }

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/v1/admin/users/search')->assertUnauthorized();
    }
}
