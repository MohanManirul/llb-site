<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Auth\ImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refuses_an_actor_without_the_permission(): void
    {
        $actor = $this->userWith(['view users'], 'Staffer');
        $target = $this->userWith(['view users'], 'Target');

        $this->actingAs($actor)
            ->post("/admin/users/{$target->id}/impersonate")
            ->assertForbidden();

        $this->assertAuthenticatedAs($actor, 'web');
    }

    public function test_an_admin_cannot_impersonate_a_super_admin(): void
    {
        $actor = $this->userWith(['impersonate users'], 'Admin');
        $target = $this->grantFullAccess(User::factory()->create(['name' => 'Owner']));

        $this->actingAs($actor)
            ->post("/admin/users/{$target->id}/impersonate")
            ->assertForbidden();

        $this->assertAuthenticatedAs($actor, 'web');
    }

    public function test_it_refuses_a_target_holding_permissions_the_actor_lacks(): void
    {
        $actor = $this->userWith(['impersonate users'], 'Supervisor');
        $target = $this->userWith(['impersonate users', 'delete users'], 'Stronger');

        $this->actingAs($actor)
            ->post("/admin/users/{$target->id}/impersonate")
            ->assertForbidden();

        $this->assertAuthenticatedAs($actor, 'web');
    }

    public function test_it_refuses_impersonating_yourself(): void
    {
        $actor = $this->superAdmin();

        $this->actingAs($actor)
            ->post("/admin/users/{$actor->id}/impersonate")
            ->assertForbidden();
    }

    public function test_it_refuses_nesting(): void
    {
        $actor = $this->superAdmin();
        $first = $this->userWith(['impersonate users'], 'First');
        $second = $this->userWith([], 'Second');

        $this->actingAs($actor)->post("/admin/users/{$first->id}/impersonate");

        $this->post("/admin/users/{$second->id}/impersonate")->assertForbidden();

        $this->assertAuthenticatedAs($first, 'web');
    }

    public function test_a_successful_impersonation_swaps_the_session(): void
    {
        $actor = $this->superAdmin();
        $target = $this->userWith(['view users'], 'Agent');

        $this->actingAs($actor)
            ->post("/admin/users/{$target->id}/impersonate")
            ->assertRedirect('/admin/dashboard')
            ->assertSessionHas(ImpersonationService::SESSION_KEY, $actor->id)
            ->assertSessionHas(ImpersonationService::SESSION_STARTED_AT);

        $this->assertAuthenticatedAs($target, 'web');
    }

    public function test_the_swap_clears_the_sanctum_password_hash(): void
    {
        $actor = $this->superAdmin();
        $target = $this->userWith([], 'Agent');

        $this->actingAs($actor)
            ->withSession(['password_hash_web' => 'a-stale-hash'])
            ->post("/admin/users/{$target->id}/impersonate")
            ->assertSessionMissing('password_hash_web');
    }

    public function test_stopping_restores_the_original_admin(): void
    {
        $actor = $this->superAdmin();
        $target = $this->userWith([], 'Agent');

        $this->actingAs($actor)->post("/admin/users/{$target->id}/impersonate");
        $this->assertAuthenticatedAs($target, 'web');

        $this->post('/admin/impersonate/stop')
            ->assertRedirect('/admin/dashboard')
            ->assertSessionMissing(ImpersonationService::SESSION_KEY)
            ->assertSessionMissing('password_hash_web');

        $this->assertAuthenticatedAs($actor, 'web');
    }

    public function test_the_stop_route_needs_no_permission(): void
    {
        $actor = $this->superAdmin();
        $target = $this->userWith([], 'Powerless');

        $this->actingAs($actor)->post("/admin/users/{$target->id}/impersonate");

        $this->assertFalse($target->fresh()->can('impersonate users'));

        $this->post('/admin/impersonate/stop')->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($actor, 'web');
    }

    public function test_stopping_leaves_the_targets_remember_token_untouched(): void
    {
        $actor = $this->superAdmin();
        $target = $this->userWith([], 'Agent');
        $target->forceFill(['remember_token' => 'keep-me-signed-in'])->save();

        $this->actingAs($actor)->post("/admin/users/{$target->id}/impersonate");
        $this->post('/admin/impersonate/stop');

        $this->assertSame('keep-me-signed-in', $target->fresh()->remember_token);
    }

    public function test_it_signs_out_when_the_original_admin_is_gone(): void
    {
        $actor = $this->superAdmin();
        $target = $this->userWith([], 'Agent');
        $target->forceFill(['remember_token' => 'keep-me-signed-in'])->save();

        $this->actingAs($actor)->post("/admin/users/{$target->id}/impersonate");

        $actor->delete();

        $this->post('/admin/impersonate/stop')->assertRedirect('/admin/login');

        $this->assertGuest('web');

        $this->assertSame('keep-me-signed-in', $target->fresh()->remember_token);
    }

    public function test_a_deleted_impersonator_leaves_the_log_naming_them(): void
    {
        $actor = $this->superAdmin('Real Admin');
        $target = $this->userWith([], 'Agent');

        $this->actingAs($actor)->post("/admin/users/{$target->id}/impersonate");
        $this->app['auth']->forgetGuards();
        $this->post('/admin/logout');

        $row = ActivityLog::where('description', 'Signed out.')->firstOrFail();
        $this->assertSame($actor->id, $row->impersonator_id);

        $actor->delete();

        $this->assertSame($actor->id, $row->fresh()->impersonator_id);
    }

    public function test_it_brackets_the_session_with_two_activity_rows(): void
    {
        $actor = $this->superAdmin('Real Admin');
        $target = $this->userWith([], 'Wornebody');

        $this->actingAs($actor)->post("/admin/users/{$target->id}/impersonate");
        $this->post('/admin/impersonate/stop');

        $started = ActivityLog::where('description', 'Started impersonating Wornebody.')->firstOrFail();
        $stopped = ActivityLog::where('description', 'Stopped impersonating Wornebody.')->firstOrFail();

        $this->assertSame($actor->id, $started->causer_id);
        $this->assertSame($actor->id, $stopped->causer_id);
        $this->assertNull($started->impersonator_id);
        $this->assertNull($stopped->impersonator_id);
    }

    public function test_work_done_while_impersonating_names_the_real_admin(): void
    {
        $actor = $this->superAdmin('Real Admin');
        $target = $this->userWith([], 'Agent');

        $this->actingAs($actor)->post("/admin/users/{$target->id}/impersonate");

        $this->app['auth']->forgetGuards();

        $this->post('/admin/logout');

        $signedOut = ActivityLog::where('description', 'Signed out.')->firstOrFail();

        $this->assertSame($target->id, $signedOut->causer_id);
        $this->assertSame($actor->id, $signedOut->impersonator_id);
    }

    public function test_the_users_api_flags_only_the_rows_that_can_be_entered(): void
    {
        $actor = $this->superAdmin('Real Admin');
        $enterable = $this->userWith(['view users'], 'Agent');
        $owner = $this->grantFullAccess(User::factory()->create(['name' => 'Other Owner']));

        $rows = collect(
            $this->actingAs($actor)
                ->getJson('/v1/admin/users')
                ->assertOk()
                ->json('result.data'),
        )->keyBy('id');

        $this->assertTrue($rows[$enterable->id]['can_impersonate']);
        $this->assertFalse($rows[$owner->id]['can_impersonate']);
        $this->assertFalse($rows[$actor->id]['can_impersonate']);
    }

    public function test_the_users_api_never_flags_a_row_without_the_permission(): void
    {
        $actor = $this->userWith(['view users'], 'Staffer');
        $this->userWith([], 'Agent');

        $rows = $this->actingAs($actor)
            ->getJson('/v1/admin/users')
            ->assertOk()
            ->json('result.data');

        $this->assertNotEmpty($rows);

        foreach ($rows as $row) {
            $this->assertFalse($row['can_impersonate']);
        }
    }

    public function test_activity_logging_survives_without_a_session(): void
    {
        $log = activity()->log('Ran from the console.');

        $this->assertNull($log->impersonator_id);
    }

    private function superAdmin(string $name = 'Super Admin'): User
    {
        return $this->grantFullAccess(
            User::factory()->create(['name' => $name, 'email' => uniqid().'@example.com']),
        );
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWith(array $permissions, string $name): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate('scoped-'.md5($name.implode(',', $permissions)), 'web');

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $user = User::factory()->create(['name' => $name, 'email' => uniqid().'@example.com']);
        $user->assignRole($role);

        return $user;
    }
}
