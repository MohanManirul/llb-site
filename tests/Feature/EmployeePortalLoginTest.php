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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * An employee is a user who works here: the employee form links an existing
 * account rather than provisioning one. After login that user only sees their
 * assigned projects.
 */
class EmployeePortalLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('employee', 'web');
        Role::findOrCreate('super-admin', 'web');
    }

    private function makeDesignation(): Designation
    {
        return Designation::firstOrCreate(['name' => 'Marketer'], ['is_active' => true]);
    }

    /**
     * @return array{0: Company, 1: Department}
     */
    private function makeOrg(): array
    {
        $company = Company::create(['name' => 'Acme', 'code' => 'ACME', 'is_active' => true]);
        $department = Department::create([
            'company_id' => $company->id, 'name' => 'Sales', 'code' => 'SAL', 'is_active' => true,
        ]);

        return [$company, $department];
    }

    private function makeEmployee(User $user, Company $company, Department $department): Employee
    {
        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'designation_id' => $this->makeDesignation()->id,
            'is_active' => true,
        ]);
    }

    public function test_creating_an_employee_links_the_picked_user(): void
    {
        [$company, $department] = $this->makeOrg();

        $target = User::factory()->create([
            'name' => 'Marketer One',
            'email' => 'marketer@example.com',
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/employees', [
                'user_id' => $target->id,
                'company_id' => $company->id,
                'department_id' => $department->id,
                'designation_id' => $this->makeDesignation()->id,
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('result.name', 'Marketer One')
            ->assertJsonPath('result.email', 'marketer@example.com');

        $employee = Employee::where('user_id', $target->id)->first();
        $this->assertNotNull($employee);

        $target->refresh();
        $this->assertTrue($target->employees->contains($employee));

        $this->assertFalse($target->hasRole('employee'));
        $this->assertContains('employee', $target->effectiveRoleNames()->all());
    }

    public function test_linking_a_user_does_not_touch_their_roles(): void
    {
        Role::findOrCreate('staff', 'web');

        [$company, $department] = $this->makeOrg();

        $target = User::factory()->create();
        $target->syncRoles(['staff']);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/employees', [
                'user_id' => $target->id,
                'company_id' => $company->id,
                'department_id' => $department->id,
                'designation_id' => $this->makeDesignation()->id,
                'is_active' => true,
            ])
            ->assertCreated();

        $target->refresh();
        $this->assertSame(['staff'], $target->getRoleNames()->all());
    }

    public function test_employee_can_sign_in_at_the_shared_web_login(): void
    {
        $user = User::factory()->create([
            'name' => 'Marketer One',
            'email' => 'marketer@example.com',
            'password' => 'password',
        ]);
        $user->syncRoles('employee');

        [$company, $department] = $this->makeOrg();

        $this->makeEmployee($user, $company, $department);

        $this->post('/admin/login', [
            'email' => 'marketer@example.com',
            'password' => 'password',
        ])->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/dashboard/page'));

        // The page itself is a shell — the report comes from the API.
        $this->getJson('/v1/admin/dashboard/report')
            ->assertOk()
            ->assertJsonPath('result.heading', 'My work — Marketer One');
    }

    public function test_employee_api_projects_are_scoped_to_assigned(): void
    {
        $owner = User::factory()->create(['name' => 'Marketer One']);
        $owner->syncRoles('employee');

        [$company, $department] = $this->makeOrg();

        $team = Team::create([
            'company_id' => $company->id, 'department_id' => $department->id,
            'name' => 'Alpha', 'is_active' => true,
        ]);

        $employee = $this->makeEmployee($owner, $company, $department);
        $otherEmployee = $this->makeEmployee(
            User::factory()->create(['name' => 'Other Marketer']),
            $company,
            $department,
        );

        DB::table('team_members')->insert([
            ['team_id' => $team->id, 'employee_id' => $employee->id, 'role' => 'member'],
            ['team_id' => $team->id, 'employee_id' => $otherEmployee->id, 'role' => 'member'],
        ]);

        $client = Client::create([
            'name' => 'Client', 'code' => 'CLI', 'email' => 'c@example.com',
            'phone' => '01711111111', 'is_active' => true,
            'password' => 'secret123',
        ]);

        $base = [
            'company_id' => $company->id,
            'department_id' => $department->id,
            'team_id' => $team->id,
            'client_id' => $client->id,
            'business_status' => BusinessStatus::CampaignRunning,
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
        ];

        $own = Project::create(array_merge($base, [
            'assigned_employee_id' => $employee->id,
            'project_name' => 'Own Biz',
            'business_name' => 'Own Biz',
        ]));
        $foreign = Project::create(array_merge($base, [
            'assigned_employee_id' => $otherEmployee->id,
            'project_name' => 'Foreign Biz',
            'business_name' => 'Foreign Biz',
        ]));

        $this->actingAs($owner)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.business_name', 'Own Biz');

        $this->actingAs($owner)
            ->getJson("/v1/admin/projects/{$own->id}")
            ->assertOk();

        $this->actingAs($owner)
            ->getJson("/v1/admin/projects/{$foreign->id}")
            ->assertForbidden();
    }

    public function test_team_leader_sees_all_projects_on_led_teams(): void
    {
        Role::findOrCreate('team-leader', 'web');

        $leaderUser = User::factory()->create(['name' => 'Leader Marketer']);
        $leaderUser->syncRoles(['employee', 'team-leader']);

        [$company, $department] = $this->makeOrg();

        $team = Team::create([
            'company_id' => $company->id, 'department_id' => $department->id,
            'name' => 'Alpha', 'is_active' => true,
        ]);

        $leader = $this->makeEmployee($leaderUser, $company, $department);
        $member = $this->makeEmployee(
            User::factory()->create(['name' => 'Member Marketer']),
            $company,
            $department,
        );

        DB::table('team_members')->insert([
            ['team_id' => $team->id, 'employee_id' => $leader->id, 'role' => 'leader'],
            ['team_id' => $team->id, 'employee_id' => $member->id, 'role' => 'member'],
        ]);

        $client = Client::create([
            'name' => 'Client', 'code' => 'CLI', 'email' => 'c@example.com',
            'phone' => '01711111111', 'is_active' => true,
            'password' => 'secret123',
        ]);

        $base = [
            'company_id' => $company->id,
            'department_id' => $department->id,
            'team_id' => $team->id,
            'client_id' => $client->id,
            'business_status' => BusinessStatus::CampaignRunning,
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
        ];

        $own = Project::create(array_merge($base, [
            'assigned_employee_id' => $leader->id,
            'project_name' => 'Leader Own',
            'business_name' => 'Leader Own',
        ]));
        $unassigned = Project::create(array_merge($base, [
            'assigned_employee_id' => null,
            'project_name' => 'Waiting Owner',
            'business_name' => 'Waiting Owner',
        ]));
        $membersProject = Project::create(array_merge($base, [
            'assigned_employee_id' => $member->id,
            'project_name' => 'Member Owned',
            'business_name' => 'Member Owned',
        ]));

        $this->actingAs($leaderUser)
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk()
            ->assertJsonCount(1, 'result.teams')
            ->assertJsonPath('result.teams.0.team.name', 'Alpha')
            ->assertJsonCount(2, 'result.teams.0.members')
            ->assertJsonCount(1, 'result.teams.0.unassigned_projects')
            ->assertJsonPath('result.teams.0.unassigned_projects.0.project', 'Waiting Owner');

        $this->actingAs($leaderUser)
            ->getJson('/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('result.sections.teams.0.name', 'Alpha')
            ->assertJsonCount(2, 'result.sections.teams.0.members')
            ->assertJsonCount(1, 'result.sections.teams.0.unassigned_projects')
            ->assertJsonPath('result.sections.teams.0.unassigned_projects.0.business_name', 'Waiting Owner');

        $this->actingAs($leaderUser)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonCount(3, 'result.data')
            ->assertJsonFragment(['business_name' => 'Leader Own'])
            ->assertJsonFragment(['business_name' => 'Waiting Owner'])
            ->assertJsonFragment(['business_name' => 'Member Owned']);

        $this->actingAs($leaderUser)
            ->getJson("/v1/admin/projects/{$unassigned->id}")
            ->assertOk();

        $this->actingAs($leaderUser)
            ->getJson("/v1/admin/projects/{$membersProject->id}")
            ->assertOk();

        $this->actingAs($leaderUser)
            ->getJson("/v1/admin/projects/{$own->id}")
            ->assertOk();
    }
}
