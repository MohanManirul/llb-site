<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
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

        // The staff roster plus the admin account, and nothing else.
        $this->assertSame(count(UserSeeder::PEOPLE) + 1, User::query()->count());
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

        $user = User::query()->firstWhere('email', 'admin@gmail.com');
        $user->update(['name' => 'Renamed By Hand']);

        $counts = [User::query()->count()];

        $this->seedAsProduction();

        $this->assertSame($counts, [User::query()->count()]);

        $this->assertSame('Renamed By Hand', $user->fresh()->name);
    }
}
