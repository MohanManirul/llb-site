<?php

namespace Tests\Feature;

use App\Enums\BusinessStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\SalesReport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApiProjectSalesReportTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    private Team $team;

    private Client $client;

    private Designation $designation;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ([
            'view projects',
            'view sales reports', 'create sales reports',
            'edit sales reports', 'delete sales reports',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('staff', 'web');

        // What the migration hands every existing role: the scoped four, which
        // reach only a project the holder is assigned to or leads.
        foreach (['employee', 'team-leader'] as $name) {
            Role::findOrCreate($name, 'web')->syncPermissions([
                'view sales reports', 'create sales reports',
                'edit sales reports', 'delete sales reports',
            ]);
        }

        $this->company = Company::create(['name' => 'Acme', 'code' => 'ACME', 'is_active' => true]);
        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Sales',
            'code' => 'SAL',
            'is_active' => true,
        ]);
        $this->team = Team::create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'name' => 'Alpha',
            'is_active' => true,
        ]);
        $this->client = Client::create([
            'name' => 'Globex',
            'code' => 'GLX',
            'email' => 'globex@example.com',
            'phone' => '01711111111',
            'is_active' => true,
            'password' => 'secret123',
        ]);
        $this->designation = Designation::firstOrCreate(
            ['name' => 'Marketer'],
            ['is_active' => true],
        );
    }

    private function makeEmployee(User $user): Employee
    {
        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeProject(array $overrides = []): Project
    {
        return Project::create(array_merge([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'team_id' => $this->team->id,
            'client_id' => $this->client->id,
            'business_status' => BusinessStatus::CampaignRunning,
            'project_name' => 'Acme Website',
            'business_name' => 'Acme Website',
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
        ], $overrides));
    }

    private function makeTeamLeader(User $user): Employee
    {
        $employee = $this->makeEmployee($user);

        DB::table('team_members')->insert([
            'team_id' => $this->team->id,
            'employee_id' => $employee->id,
            'role' => 'leader',
        ]);

        return $employee;
    }

    private function staffWith(string ...$permissions): User
    {
        $role = Role::findByName('staff', 'web');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->syncRoles(['staff']);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function reportPayload(): array
    {
        return [
            'week_start' => '2026-01-05',
            'week_end' => '2026-01-11',
            'total_sales' => 1000,
            'total_order_quantity' => 10,
            'total_amount_spent' => 200,
        ];
    }

    /**
     * A team's leader reads its weeks but does not write them: submitting is
     * tied to the assigned employee, and the leader is not that person.
     */
    public function test_the_team_leader_can_list_the_reports_but_not_submit_one(): void
    {
        $project = $this->makeProject();
        SalesReport::create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            ...$this->reportPayload(),
        ]);

        $viewer = $this->staffWith('view projects', 'view sales reports');
        $this->makeTeamLeader($viewer);

        $this->actingAs($viewer)
            ->getJson("/v1/admin/projects/{$project->id}/sales-reports")
            ->assertOk()
            ->assertJsonCount(1, 'result.data');

        $this->actingAs($viewer)
            ->postJson("/v1/admin/projects/{$project->id}/sales-reports", [
                'week_start' => '2026-02-02',
                'week_end' => '2026-02-08',
                'total_sales' => 1000,
                'total_order_quantity' => 10,
                'total_amount_spent' => 200,
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonPath('result.data.0.can_view_reports', true)
            ->assertJsonPath('result.data.0.can_submit_reports', false);

        $this->actingAs($viewer)
            ->get("/admin/projects/{$project->id}/reports")
            ->assertOk();
    }

    public function test_super_admin_can_submit_a_report_on_any_project(): void
    {
        $project = $this->makeProject();
        $editor = $this->grantFullAccess(User::factory()->create());

        $this->actingAs($editor)
            ->postJson("/v1/admin/projects/{$project->id}/sales-reports", $this->reportPayload())
            ->assertCreated();

        $this->actingAs($editor)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonPath('result.data.0.can_view_reports', true)
            ->assertJsonPath('result.data.0.can_submit_reports', true);
    }

    /**
     * `edit sales reports` used to reach every project's reports. It now stops
     * at the ones the holder is assigned to, so a colleague's week is safe.
     */
    public function test_edit_sales_reports_stops_at_the_holders_own_project(): void
    {
        $owner = User::factory()->create();
        $owner->syncRoles(['employee']);
        $employee = $this->makeEmployee($owner);

        $mine = $this->makeProject(['assigned_employee_id' => $employee->id]);
        $theirs = $this->makeProject([
            'project_name' => 'Theirs',
            'assigned_employee_id' => $this->makeEmployee(User::factory()->create())->id,
        ]);

        $mineReport = SalesReport::create([
            'company_id' => $this->company->id,
            'project_id' => $mine->id,
            ...$this->reportPayload(),
        ]);
        $theirsReport = SalesReport::create([
            'company_id' => $this->company->id,
            'project_id' => $theirs->id,
            ...$this->reportPayload(),
        ]);

        $this->actingAs($owner)
            ->patchJson(
                "/v1/admin/projects/{$mine->id}/sales-reports/{$mineReport->id}",
                [...$this->reportPayload(), 'total_sales' => 2000],
            )
            ->assertOk();

        $this->actingAs($owner)
            ->patchJson(
                "/v1/admin/projects/{$theirs->id}/sales-reports/{$theirsReport->id}",
                [...$this->reportPayload(), 'total_sales' => 2000],
            )
            ->assertForbidden();

        $this->actingAs($owner)
            ->deleteJson("/v1/admin/projects/{$theirs->id}/sales-reports/{$theirsReport->id}")
            ->assertForbidden();
    }

    public function test_assigned_employee_can_view_and_submit_on_their_own_project(): void
    {
        $owner = User::factory()->create();
        $owner->syncRoles(['employee']);
        $employee = $this->makeEmployee($owner);
        DB::table('team_members')->insert([
            'team_id' => $this->team->id,
            'employee_id' => $employee->id,
            'role' => 'member',
        ]);

        $project = $this->makeProject(['assigned_employee_id' => $employee->id]);

        $this->actingAs($owner)
            ->getJson("/v1/admin/projects/{$project->id}/sales-reports")
            ->assertOk();

        $this->actingAs($owner)
            ->postJson("/v1/admin/projects/{$project->id}/sales-reports", $this->reportPayload())
            ->assertCreated();

        $this->actingAs($owner)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonPath('result.data.0.can_view_reports', true)
            ->assertJsonPath('result.data.0.can_submit_reports', true);

        $this->actingAs($owner)
            ->get("/admin/projects/{$project->id}/reports")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/projects/reports/page')
                ->where('canSubmitReports', true));
    }

    public function test_team_leader_can_view_but_not_submit_on_a_members_project(): void
    {
        $leaderUser = User::factory()->create();
        $leaderUser->syncRoles(['employee', 'team-leader']);
        $leader = $this->makeEmployee($leaderUser);
        DB::table('team_members')->insert([
            'team_id' => $this->team->id,
            'employee_id' => $leader->id,
            'role' => 'leader',
        ]);

        $member = $this->makeEmployee(User::factory()->create());
        DB::table('team_members')->insert([
            'team_id' => $this->team->id,
            'employee_id' => $member->id,
            'role' => 'member',
        ]);

        $project = $this->makeProject(['assigned_employee_id' => $member->id]);

        $this->actingAs($leaderUser)
            ->getJson("/v1/admin/projects/{$project->id}/sales-reports")
            ->assertOk();

        $this->actingAs($leaderUser)
            ->postJson("/v1/admin/projects/{$project->id}/sales-reports", $this->reportPayload())
            ->assertForbidden();

        $this->actingAs($leaderUser)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonPath('result.data.0.can_view_reports', true)
            ->assertJsonPath('result.data.0.can_submit_reports', false);

        $this->actingAs($leaderUser)
            ->get("/admin/projects/{$project->id}/reports")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/projects/reports/page')
                ->where('canSubmitReports', false));
    }

    public function test_unrelated_employee_cannot_access_reports(): void
    {
        $outsider = User::factory()->create();
        $outsider->syncRoles(['employee']);
        $this->makeEmployee($outsider);

        $assigned = User::factory()->create();
        $assigned->syncRoles(['employee']);
        $assignee = $this->makeEmployee($assigned);
        DB::table('team_members')->insert([
            'team_id' => $this->team->id,
            'employee_id' => $assignee->id,
            'role' => 'member',
        ]);

        $project = $this->makeProject(['assigned_employee_id' => $assignee->id]);

        $this->actingAs($outsider)
            ->getJson("/v1/admin/projects/{$project->id}/sales-reports")
            ->assertForbidden();

        $this->actingAs($outsider)
            ->postJson("/v1/admin/projects/{$project->id}/sales-reports", $this->reportPayload())
            ->assertForbidden();

        $this->actingAs($outsider)
            ->get("/admin/projects/{$project->id}/reports")
            ->assertForbidden();
    }
}
