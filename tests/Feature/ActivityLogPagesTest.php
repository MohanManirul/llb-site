<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/activity-logs')->assertRedirect('/admin/login');
    }

    public function test_a_super_admin_can_open_the_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/activity-logs')
            ->assertOk();
    }

    public function test_staff_cannot_open_the_page(): void
    {
        $this->seed(UserSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(Role::findByName('staff', 'web'));

        $this->actingAs($staff)->get('/admin/activity-logs')->assertForbidden();
    }

    public function test_settings_falls_through_to_activity_logs_when_it_is_the_only_section(): void
    {
        $this->seed(UserSeeder::class);

        // /settings forwards to the first section the user may open; with only
        // view activity logs granted, that is the activity log.
        // The role matters: TestCase::actingAs promotes a role-less user to
        // super-admin, who would pass another section's permission and land there.
        $role = Role::findOrCreate('auditor', 'web');
        $role->syncPermissions(['view activity logs']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get('/admin/settings')->assertRedirect('/admin/activity-logs');
    }
}
