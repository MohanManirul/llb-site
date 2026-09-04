<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\CompanySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\DesignationSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The full seeder is meant to be safe to run on a live install, so what it
 * produces there is pinned here: the real rows, none of the demo volume, and
 * the same result whether it runs once or twice.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedAsProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->withoutMockingConsoleOutput();
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);
    }

    public function test_seeding_a_production_install_creates_only_the_real_rows(): void
    {
        $this->seedAsProduction();

        $this->assertSame(count(CompanySeeder::COMPANIES), Company::query()->count());
        $this->assertSame(
            count(CompanySeeder::COMPANIES) * count(DepartmentSeeder::NAMES),
            Department::query()->count(),
        );
        $this->assertSame(count(DesignationSeeder::NAMES), Designation::query()->count());

        // The staff roster plus the admin account, and nothing else.
        $this->assertSame(count(UserSeeder::PEOPLE) + 1, User::query()->count());
        $this->assertSame(count(UserSeeder::PEOPLE), Employee::query()->count());
    }

    public function test_the_roles_it_creates_are_the_ones_the_app_expects(): void
    {
        $this->seedAsProduction();

        $this->assertEqualsCanonicalizing(
            [
                UserSeeder::SUPER_ADMIN,
                UserSeeder::ADMIN,
                UserSeeder::STAFF,
            ],
            Role::query()->pluck('name')->all(),
        );
    }

    /** Re-running it must not double anything, nor undo an edit made since. */
    public function test_a_second_run_changes_nothing(): void
    {
        $this->seedAsProduction();

        $company = Company::query()->first();
        $company->update(['address' => 'Moved, Gulshan 2, Dhaka']);

        $counts = [
            User::query()->count(),
            Company::query()->count(),
            Department::query()->count(),
            Employee::query()->count(),
        ];

        $this->seedAsProduction();

        $this->assertSame($counts, [
            User::query()->count(),
            Company::query()->count(),
            Department::query()->count(),
            Employee::query()->count(),
        ]);

        $this->assertSame('Moved, Gulshan 2, Dhaka', $company->fresh()->address);
    }
}
