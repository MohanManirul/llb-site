<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * PermissionSeeder is the one seeder meant to be run against production by
 * hand, so what it must not do matters more than what it does. It may insert a
 * missing permission row. It may not grant, revoke, or delete anything --
 * `role_has_permissions` cascades on delete, so a prune added here later would
 * silently wipe every grant an admin made through the Roles screen, which is
 * the outcome splitting it out of UserSeeder existed to prevent.
 *
 * UserSeeder cannot pin any of this: it calls this seeder and then syncs all
 * five roles, repairing the damage before an assertion could see it.
 */
class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, string>
     */
    private function configured(): array
    {
        return config('admin-permissions.admin');
    }

    public function test_it_creates_every_permission_the_config_names(): void
    {
        $this->seed(PermissionSeeder::class);

        $this->assertSame(
            count($this->configured()),
            Permission::query()->where('guard_name', 'web')->count(),
        );

        $this->assertNotNull(
            Permission::query()->firstWhere('name', 'view roles'),
        );
    }

    /**
     * The property the whole class exists for. A supervisor role somebody built
     * by hand keeps exactly the permissions they gave it.
     */
    public function test_a_grant_made_by_hand_survives_a_run(): void
    {
        $this->seed(PermissionSeeder::class);

        $role = Role::findOrCreate('floor-lead', 'web');
        $role->givePermissionTo('view users', 'view roles');

        $pivotsBefore = DB::table('role_has_permissions')->count();

        $this->seed(PermissionSeeder::class);

        $this->assertSame($pivotsBefore, DB::table('role_has_permissions')->count());
        $this->assertEqualsCanonicalizing(
            ['view users', 'view roles'],
            $role->fresh()->permissions->pluck('name')->all(),
            'a role built by hand keeps exactly what it was given',
        );
    }

    /**
     * Additive on purpose: dropping a name from the config does not reach a
     * deployed database, so removing the row stays a migration's job.
     */
    public function test_a_permission_the_config_no_longer_names_keeps_its_row(): void
    {
        $this->seed(PermissionSeeder::class);

        $stray = Permission::findOrCreate('view something retired', 'web');

        $this->seed(PermissionSeeder::class);

        $this->assertNotNull(
            Permission::query()->find($stray->id),
            'the seeder prunes nothing -- a migration does that',
        );
    }

    public function test_a_second_run_leaves_every_existing_row_untouched(): void
    {
        $this->seed(PermissionSeeder::class);

        $snapshot = fn () => Permission::query()
            ->orderBy('id')
            ->get(['id', 'name', 'guard_name', 'created_at', 'updated_at'])
            ->toJson();

        $before = $snapshot();

        $this->seed(PermissionSeeder::class);

        $this->assertSame($before, $snapshot(), 'ids and timestamps both stay put');
    }
}
