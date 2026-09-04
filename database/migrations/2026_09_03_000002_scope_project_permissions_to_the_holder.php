<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * A project permission now means the holder's **own** projects — the ones they
 * are assigned to or lead the team of — and there is no second, wider
 * permission beside it. `view projects`, `edit projects` and `delete projects`
 * used to reach every row; the eight `… all …` permissions that briefly carried
 * that old meaning are removed here, along with the roles' grants of them.
 *
 * The single exception is super-admin, and it is answered in code, not by a
 * permission row: `ChecksProjectAccess::reachesEveryProject()` and
 * `ProjectService::paginateForUser()` let that role past, the same way
 * `AppServiceProvider`'s `Gate::before` answers every other ability for it.
 *
 * Notes and weekly sales reports gain the four scoped permissions each that
 * they never had — an assigned employee used to submit a report with no
 * permission at all — so every existing role is given them. They grant nothing
 * on their own: without a project of the holder's own they answer false,
 * exactly as before. An admin can untick what a role should not have.
 */
return new class extends Migration
{
    private const SCOPED = [
        'view project notes',
        'create project notes',
        'edit project notes',
        'delete project notes',
        'view sales reports',
        'create sales reports',
        'edit sales reports',
        'delete sales reports',
    ];

    private const UNSCOPED = [
        'view all projects',
        'edit all projects',
        'delete all projects',
        'manage all project notes',
        'view all sales reports',
        'create all sales reports',
        'edit all sales reports',
        'delete all sales reports',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $scoped = array_map(
            fn (string $name) => Permission::findOrCreate($name, 'web'),
            self::SCOPED,
        );

        Role::query()
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($scoped));

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::UNSCOPED)
            ->get()
            ->each(fn (Permission $permission) => $permission->delete());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                'view project notes',
                'create project notes',
                'edit project notes',
                'delete project notes',
            ])
            ->get()
            ->each(fn (Permission $permission) => $permission->delete());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
