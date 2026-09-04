<?php

namespace Tests\Feature;

use App\Enums\BusinessStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Client column ta permission-gated: team leader default e client dekhe na,
 * super-admin tar role e view project client tick korle tobei dekhe.
 */
class ProjectClientColumnPermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $leaderUser;

    private int $projectId;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('view project client', 'web');
        Role::findOrCreate('employee', 'web');
        Role::findOrCreate('team-leader', 'web');

        $this->leaderUser = User::factory()->create();
        $this->leaderUser->syncRoles(['employee', 'team-leader']);

        $company = Company::create(['name' => 'Acme', 'code' => 'ACME', 'is_active' => true]);
        $department = Department::create([
            'company_id' => $company->id, 'name' => 'Sales', 'code' => 'SAL', 'is_active' => true,
        ]);
        $team = Team::create([
            'company_id' => $company->id, 'department_id' => $department->id,
            'name' => 'Alpha', 'is_active' => true,
        ]);

        $leader = Employee::create([
            'user_id' => $this->leaderUser->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'designation_id' => Designation::firstOrCreate(
                ['name' => 'Marketer'],
                ['is_active' => true],
            )->id,
            'is_active' => true,
        ]);

        DB::table('team_members')->insert([
            ['team_id' => $team->id, 'employee_id' => $leader->id, 'role' => 'leader'],
        ]);

        $this->client = Client::create([
            'name' => 'Globex Ltd', 'code' => 'GLX', 'email' => 'globex@example.com',
            'phone' => '01711111111', 'is_active' => true,
            'password' => 'secret123',
        ]);

        $this->projectId = Project::create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'team_id' => $team->id,
            'client_id' => $this->client->id,
            'assigned_employee_id' => $leader->id,
            'business_status' => BusinessStatus::CampaignRunning,
            'project_name' => 'Leader Own',
            'business_name' => 'Leader Own',
            'contact_person' => 'A',
            'contact_email' => 'a@example.com',
            'contact_phone' => '01700000000',
            'project_type' => 'regular',
            'package_amount' => 1000,
            'amount_paid' => 0,
            'contract_months' => 12,
            'start_date' => '2026-01-01',
            'end_date' => '2027-01-01',
            'health_status' => 'upcoming',
        ])->id;
    }

    public function test_a_team_leader_without_the_permission_gets_no_client_data(): void
    {
        $this->actingAs($this->leaderUser)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonPath('result.data.0.business_name', 'Leader Own')
            ->assertJsonPath('result.data.0.client', null)
            ->assertJsonPath('result.data.0.client_email', null)
            ->assertJsonPath('result.data.0.client_phone', null);
    }

    public function test_the_client_name_is_not_searchable_without_the_permission(): void
    {
        $this->actingAs($this->leaderUser)
            ->getJson('/v1/admin/projects?search=Globex')
            ->assertOk()
            ->assertJsonCount(0, 'result.data');
    }

    public function test_ticking_the_permission_on_the_role_brings_the_client_back(): void
    {
        Role::findByName('team-leader', 'web')->givePermissionTo('view project client');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($this->leaderUser)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonPath('result.data.0.client', 'Globex Ltd')
            ->assertJsonPath('result.data.0.client_email', 'globex@example.com');

        $this->actingAs($this->leaderUser)
            ->getJson('/v1/admin/projects?search=Globex')
            ->assertOk()
            ->assertJsonCount(1, 'result.data');
    }

    public function test_the_project_detail_page_hides_the_client_block_too(): void
    {
        $this->actingAs($this->leaderUser)
            ->getJson("/v1/admin/projects/{$this->projectId}")
            ->assertOk()
            ->assertJsonPath('result.business_name', 'Leader Own')
            ->assertJsonMissingPath('result.client')
            ->assertJsonMissingPath('result.client_name');

        Role::findByName('team-leader', 'web')->givePermissionTo('view project client');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($this->leaderUser)
            ->getJson("/v1/admin/projects/{$this->projectId}")
            ->assertOk()
            ->assertJsonPath('result.client.name', 'Globex Ltd')
            ->assertJsonPath('result.client.phone', '01711111111');
    }

    public function test_a_client_still_sees_their_own_project_details(): void
    {
        $this->actingAs($this->client, 'client')
            ->getJson("/v1/client/projects/{$this->projectId}")
            ->assertOk()
            ->assertJsonPath('result.client.name', 'Globex Ltd');
    }

    public function test_the_dashboard_team_tables_follow_the_same_permission(): void
    {
        $this->actingAs($this->leaderUser)
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk()
            ->assertJsonMissingPath('result.teams.0.members.0.projects.0.client');

        Role::findByName('team-leader', 'web')->givePermissionTo('view project client');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($this->leaderUser)
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk()
            ->assertJsonPath('result.teams.0.members.0.projects.0.client', 'Globex Ltd');
    }
}
