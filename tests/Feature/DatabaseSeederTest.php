<?php

namespace Tests\Feature;

use App\Models\CallCenterAgent;
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

    public function test_it_puts_the_call_center_agents_on_the_roster(): void
    {
        $this->seedAsProduction();

        $expected = count(array_filter(
            UserSeeder::PEOPLE,
            fn (array $person) => $person['call_center'] ?? false,
        ));

        $this->assertSame($expected, CallCenterAgent::query()->count());

        $supervisor = User::query()->firstWhere('email', 'tanvir.ahmed@boneek.com.bd');
        $this->assertTrue($supervisor->hasRole(UserSeeder::CALL_CENTER_SUPERVISOR));
        $this->assertTrue($supervisor->can('manage call center agents'));

        $agent = User::query()->firstWhere('email', 'sadia.islam@boneek.com.bd');
        $this->assertTrue($agent->can('pick call center orders'));
        $this->assertFalse(
            $agent->can('view all call center orders'),
            'an agent sees only their own picks',
        );

        // Each agent is credited to the company their employment names.
        $this->assertSame(
            Employee::query()->where('user_id', $agent->id)->value('id'),
            CallCenterAgent::query()->where('user_id', $agent->id)->value('employee_id'),
        );
    }

    public function test_the_roles_it_creates_are_the_ones_the_app_expects(): void
    {
        $this->seedAsProduction();

        $this->assertEqualsCanonicalizing(
            [
                UserSeeder::SUPER_ADMIN,
                UserSeeder::ADMIN,
                UserSeeder::STAFF,
                UserSeeder::CALL_CENTER_AGENT,
                UserSeeder::CALL_CENTER_SUPERVISOR,
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
            CallCenterAgent::query()->count(),
        ];

        $this->seedAsProduction();

        $this->assertSame($counts, [
            User::query()->count(),
            Company::query()->count(),
            Department::query()->count(),
            Employee::query()->count(),
            CallCenterAgent::query()->count(),
        ]);

        $this->assertSame('Moved, Gulshan 2, Dhaka', $company->fresh()->address);
    }
}
