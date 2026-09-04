<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Every permission `config/admin-permissions.php` names, as rows, and nothing
 * else. It grants nothing and revokes nothing, so it is the one seeder that is
 * safe to run against a live database: `UserSeeder` follows it with
 * `syncPermissions()`, which would replace whatever an admin had granted a role
 * by hand, and creates staff accounts besides.
 *
 * Additive on purpose. A permission dropped from the config keeps its row here;
 * deleting it is a migration's job, as `drop_product_details_table` does, because
 * only a migration reaches a deployed database.
 *
 *     php artisan db:seed --class=PermissionSeeder --force
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);

        $registrar->forgetCachedPermissions();

        /** @var array<int, string> $permissions */
        $permissions = config('admin-permissions.admin', []);

        $existing = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        $added = array_values(array_diff($permissions, $existing));

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $registrar->forgetCachedPermissions();

        $this->report($permissions, $added);
    }

    /**
     * @param  array<int, string>  $permissions
     * @param  array<int, string>  $added
     */
    private function report(array $permissions, array $added): void
    {
        if ($this->command === null) {
            return;
        }

        if ($added === []) {
            $this->command->info(count($permissions).' permissions already in place, nothing added');

            return;
        }

        $this->command->info(count($added).' permission(s) added:');

        foreach ($added as $permission) {
            $this->command->line('  '.$permission);
        }

        $this->command->warn('Nobody holds them yet — grant them in Admin → Roles, or run UserSeeder on a database whose roles you are happy to resync.');
    }
}
