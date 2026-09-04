<?php

namespace Tests\Feature;

use App\Enums\BusinessStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApiProjectNoteTest extends TestCase
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
            'view projects', 'edit projects',
            'view project notes', 'create project notes', 'edit project notes',
            'delete project notes',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('staff', 'web');

        // What the migration hands every existing role: the scoped four, which
        // reach only a project the holder is assigned to or leads.
        foreach (['employee', 'team-leader'] as $name) {
            Role::findOrCreate($name, 'web')->syncPermissions([
                'view project notes', 'create project notes',
                'edit project notes', 'delete project notes',
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

    private function staffWith(string ...$permissions): User
    {
        $role = Role::findByName('staff', 'web');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->syncRoles(['staff']);

        return $user;
    }

    /**
     * The note permissions are not a licence over every project. A staff member
     * holding them but assigned to nothing reaches no notes at all — and the
     * project itself is not even on their list.
     */
    public function test_the_note_permissions_need_a_project_of_your_own(): void
    {
        $project = $this->makeProject();
        $viewer = $this->staffWith(
            'view projects',
            'view project notes',
            'create project notes',
        );

        $this->actingAs($viewer)
            ->getJson("/v1/admin/projects/{$project->id}/notes")
            ->assertForbidden();

        $this->actingAs($viewer)
            ->postJson("/v1/admin/projects/{$project->id}/notes", ['note' => 'Nope'])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonCount(0, 'result.data');
    }

    public function test_super_admin_reaches_the_notes_of_every_project(): void
    {
        $project = $this->makeProject();
        $superAdmin = $this->grantFullAccess(User::factory()->create());

        $this->actingAs($superAdmin)
            ->getJson("/v1/admin/projects/{$project->id}/notes")
            ->assertOk();

        $this->actingAs($superAdmin)
            ->postJson("/v1/admin/projects/{$project->id}/notes", ['note' => 'Staff note'])
            ->assertCreated()
            ->assertJsonPath('result.note', 'Staff note');

        $this->actingAs($superAdmin)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonPath('result.data.0.can_view_notes', true)
            ->assertJsonPath('result.data.0.can_add_notes', true);
    }

    /**
     * `ProjectNoteService::listForProject` filters on `user_id`, so a project's
     * notes are private to whoever wrote them — reaching every project opens
     * the endpoint, not other people's notes.
     */
    public function test_a_note_is_listed_only_to_the_person_who_wrote_it(): void
    {
        $project = $this->makeProject();
        ProjectNote::create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'user_id' => User::factory()->create()->id,
            'note' => 'Someone else note',
        ]);

        $superAdmin = $this->grantFullAccess(User::factory()->create());

        $this->actingAs($superAdmin)
            ->getJson("/v1/admin/projects/{$project->id}/notes")
            ->assertOk()
            ->assertJsonCount(0, 'result.data');
    }

    public function test_view_project_notes_alone_cannot_add_one(): void
    {
        $owner = User::factory()->create();
        $owner->syncRoles(['employee']);
        Role::findByName('employee', 'web')->syncPermissions(['view project notes']);
        $employee = $this->makeEmployee($owner);

        $project = $this->makeProject(['assigned_employee_id' => $employee->id]);

        $this->actingAs($owner)
            ->getJson("/v1/admin/projects/{$project->id}/notes")
            ->assertOk();

        $this->actingAs($owner)
            ->postJson("/v1/admin/projects/{$project->id}/notes", ['note' => 'Nope'])
            ->assertForbidden();
    }

    public function test_assigned_employee_can_view_and_add_notes(): void
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
            ->getJson("/v1/admin/projects/{$project->id}/notes")
            ->assertOk();

        $this->actingAs($owner)
            ->postJson("/v1/admin/projects/{$project->id}/notes", ['note' => 'Owner note'])
            ->assertCreated()
            ->assertJsonPath('result.note', 'Owner note');

        $this->actingAs($owner)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonPath('result.data.0.can_view_notes', true)
            ->assertJsonPath('result.data.0.can_add_notes', true);
    }

    public function test_team_leader_can_view_and_add_notes_on_led_team_projects(): void
    {
        $leaderUser = User::factory()->create();
        $leaderUser->syncRoles(['employee', 'team-leader']);
        $leader = $this->makeEmployee($leaderUser);
        DB::table('team_members')->insert([
            'team_id' => $this->team->id,
            'employee_id' => $leader->id,
            'role' => 'leader',
        ]);

        $project = $this->makeProject(['assigned_employee_id' => null]);

        $this->actingAs($leaderUser)
            ->getJson("/v1/admin/projects/{$project->id}/notes")
            ->assertOk();

        $this->actingAs($leaderUser)
            ->postJson("/v1/admin/projects/{$project->id}/notes", ['note' => 'Leader note'])
            ->assertCreated()
            ->assertJsonPath('result.note', 'Leader note');

        $this->actingAs($leaderUser)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonPath('result.data.0.can_view_notes', true)
            ->assertJsonPath('result.data.0.can_add_notes', true);
    }

    public function test_unrelated_employee_cannot_access_notes(): void
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
            ->getJson("/v1/admin/projects/{$project->id}/notes")
            ->assertForbidden();

        $this->actingAs($outsider)
            ->postJson("/v1/admin/projects/{$project->id}/notes", ['note' => 'Nope'])
            ->assertForbidden();
    }
}
