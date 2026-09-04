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
 * `edit projects` and `delete projects` used to reach every row, so an employee
 * granted either one could rewrite a colleague's project. They now reach only
 * the projects the holder is assigned to or leads the team of, and there is no
 * second permission beside them that widens it — super-admin is the one role
 * that still reaches everything, answered in code rather than by a row.
 */
class ApiProjectScopedPermissionTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    private Team $team;

    private Designation $designation;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            'view projects', 'edit projects', 'delete projects',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('staff', 'web');

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
        $this->designation = Designation::firstOrCreate(
            ['name' => 'Marketer'],
            ['is_active' => true],
        );
        $this->client = Client::create([
            'name' => 'Globex',
            'code' => 'GLX',
            'email' => 'globex@example.com',
            'phone' => '01711111111',
            'is_active' => true,
            'password' => 'secret123',
        ]);
    }

    private function staffWith(string ...$permissions): User
    {
        $role = Role::findByName('staff', 'web');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->syncRoles(['staff']);

        return $user;
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

    private function makeProject(string $name, ?Employee $assigned = null): Project
    {
        return Project::create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'team_id' => $this->team->id,
            'client_id' => $this->client->id,
            'assigned_employee_id' => $assigned?->id,
            'business_status' => BusinessStatus::CampaignRunning,
            'project_name' => $name,
            'business_name' => $name,
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
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function updatePayload(Project $project, string $name): array
    {
        return [
            'company_id' => $project->company_id,
            'department_id' => $project->department_id,
            'team_id' => $project->team_id,
            'client_id' => $this->client->id,
            'project_name' => $name,
            'business_name' => $name,
            'description' => 'Scope check',
            'contact_person' => 'A',
            'contact_email' => 'a@example.com',
            'contact_phone' => '01700000000',
            'project_type' => 'regular',
            'package_amount' => 1000,
            'amount_paid' => 0,
            'contract_months' => 12,
            'start_date' => '2026-01-01',
            'next_payment_date' => '2026-02-01',
            'business_status' => BusinessStatus::CampaignRunning->value,
        ];
    }

    public function test_edit_projects_only_reaches_the_holders_own_project(): void
    {
        $user = $this->staffWith('view projects', 'edit projects');
        $mine = $this->makeProject('Mine', $this->makeEmployee($user));
        $theirs = $this->makeProject('Theirs', $this->makeEmployee(User::factory()->create()));

        $this->actingAs($user)
            ->putJson("/v1/admin/projects/{$mine->id}", $this->updatePayload($mine, 'Mine Renamed'))
            ->assertOk();

        $this->actingAs($user)
            ->putJson("/v1/admin/projects/{$theirs->id}", $this->updatePayload($theirs, 'Theirs Renamed'))
            ->assertForbidden();

        $this->assertSame('Theirs', $theirs->fresh()->project_name);
    }

    public function test_super_admin_reaches_every_project_without_holding_one(): void
    {
        $superAdmin = $this->grantFullAccess(User::factory()->create());
        $theirs = $this->makeProject('Theirs', $this->makeEmployee(User::factory()->create()));

        $this->actingAs($superAdmin)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.id', $theirs->id);

        $this->actingAs($superAdmin)
            ->putJson("/v1/admin/projects/{$theirs->id}", $this->updatePayload($theirs, 'Theirs Renamed'))
            ->assertOk();

        $this->assertSame('Theirs Renamed', $theirs->fresh()->project_name);
    }

    public function test_the_team_leader_may_edit_the_teams_projects(): void
    {
        $leader = $this->staffWith('view projects', 'edit projects');
        $employee = $this->makeEmployee($leader);

        DB::table('team_members')->insert([
            'team_id' => $this->team->id,
            'employee_id' => $employee->id,
            'role' => 'leader',
        ]);

        $project = $this->makeProject('Team project', $this->makeEmployee(User::factory()->create()));

        $this->actingAs($leader)
            ->putJson("/v1/admin/projects/{$project->id}", $this->updatePayload($project, 'Team renamed'))
            ->assertOk();
    }

    public function test_delete_projects_only_reaches_the_holders_own_project(): void
    {
        $user = $this->staffWith('view projects', 'delete projects');
        $mine = $this->makeProject('Mine', $this->makeEmployee($user));
        $theirs = $this->makeProject('Theirs', $this->makeEmployee(User::factory()->create()));

        $this->actingAs($user)
            ->deleteJson("/v1/admin/projects/{$theirs->id}")
            ->assertForbidden();

        $this->actingAs($user)
            ->deleteJson("/v1/admin/projects/{$mine->id}")
            ->assertOk();

        $this->assertSoftDeleted('projects', ['id' => $mine->id]);
        $this->assertNotSoftDeleted('projects', ['id' => $theirs->id]);
    }

    public function test_the_list_marks_each_row_with_what_the_holder_may_do(): void
    {
        $editor = $this->staffWith('view projects', 'edit projects');
        $mine = $this->makeProject('Mine', $this->makeEmployee($editor));

        $this->assertTrue($this->canEditFlag($editor, $mine));

        // The same row, read by someone who may see it but not change it: the
        // team's leader, holding `view projects` on its own.
        $reader = $this->staffWith('view projects');
        $employee = $this->makeEmployee($reader);

        DB::table('team_members')->insert([
            'team_id' => $this->team->id,
            'employee_id' => $employee->id,
            'role' => 'leader',
        ]);

        $this->assertFalse($this->canEditFlag($reader, $mine));
    }

    private function canEditFlag(User $user, Project $project): bool
    {
        $rows = $this->actingAs($user)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->json('result.data');

        return (bool) collect($rows)->pluck('can_edit', 'id')->get($project->id);
    }

    public function test_view_projects_alone_no_longer_lists_every_project(): void
    {
        $user = $this->staffWith('view projects');
        $mine = $this->makeProject('Mine', $this->makeEmployee($user));
        $this->makeProject('Theirs', $this->makeEmployee(User::factory()->create()));

        $this->actingAs($user)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.id', $mine->id);
    }

    public function test_the_edit_page_refuses_a_project_the_holder_does_not_own(): void
    {
        $user = $this->staffWith('view projects', 'edit projects');
        $mine = $this->makeProject('Mine', $this->makeEmployee($user));
        $theirs = $this->makeProject('Theirs', $this->makeEmployee(User::factory()->create()));

        $this->actingAs($user)->get("/admin/projects/{$mine->id}/edit")->assertOk();
        $this->actingAs($user)->get("/admin/projects/{$theirs->id}/edit")->assertForbidden();
    }

    /**
     * The two dashboard sections a team leader sees. `scope=assigned` is the
     * work they carry themselves; `scope=led` is their whole team's, their own
     * assigned project included — the two overlap there on purpose, because a
     * leader answers for every project on the team, that one as well.
     */
    public function test_the_scope_filter_separates_a_leaders_own_projects_from_their_teams(): void
    {
        $leader = $this->staffWith('view projects');
        $employee = $this->makeEmployee($leader);

        DB::table('team_members')->insert([
            'team_id' => $this->team->id,
            'employee_id' => $employee->id,
            'role' => 'leader',
        ]);

        $mine = $this->makeProject('Mine', $employee);
        $teammates = $this->makeProject('Teammates', $this->makeEmployee(User::factory()->create()));
        $unassigned = $this->makeProject('Unassigned');

        $this->actingAs($leader)
            ->getJson('/v1/admin/projects?scope=assigned')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.id', $mine->id);

        $led = $this->actingAs($leader)
            ->getJson('/v1/admin/projects?scope=led')
            ->assertOk()
            ->json('result.data');

        $this->assertEqualsCanonicalizing(
            [$mine->id, $teammates->id, $unassigned->id],
            collect($led)->pluck('id')->all(),
        );
    }

    public function test_the_led_scope_is_empty_for_an_employee_who_leads_nothing(): void
    {
        $user = $this->staffWith('view projects');
        $this->makeProject('Mine', $this->makeEmployee($user));

        $this->actingAs($user)
            ->getJson('/v1/admin/projects?scope=led')
            ->assertOk()
            ->assertJsonCount(0, 'result.data');
    }
}
