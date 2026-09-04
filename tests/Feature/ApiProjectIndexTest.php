<?php

namespace Tests\Feature;

use App\Enums\BusinessStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectSale;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Covers what Projects/Index.jsx + Projects/Show.jsx rely on now that the pages
 * carry no server props: the API has to do the searching, filtering, sorting,
 * paginating and option-listing that the old Inertia controller did.
 */
class ApiProjectIndexTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    private Team $team;

    private Employee $employee;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Acme Corp', 'code' => 'ACME', 'is_active' => true]);
        $this->department = $this->makeDepartment('Engineering', 'ENG');
        $this->team = $this->makeTeam('Alpha Team', $this->department);
        $this->employee = $this->makeEmployee('Rakib Hasan', 'rakib');
        $this->addTeamMember($this->team, $this->employee);
        $this->client = $this->makeClient('Globex Ltd', 'GLX');
    }

    private function makeDepartment(string $name, string $code, ?Company $company = null): Department
    {
        return Department::create([
            'company_id' => ($company ?? $this->company)->id,
            'name' => $name,
            'code' => $code,
            'is_active' => true,
        ]);
    }

    private function makeTeam(string $name, Department $department): Team
    {
        return Team::create([
            'company_id' => $department->company_id,
            'department_id' => $department->id,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    /** Identity lives on `users`, so an employee always needs a user first. */
    private function makeEmployee(string $name, string $code, ?Department $department = null): Employee
    {
        $department ??= $this->department;

        $user = User::factory()->create([
            'name' => $name,
            'email' => "{$code}@example.com",
        ]);

        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $department->company_id,
            'department_id' => $department->id,
            'designation_id' => Designation::firstOrCreate(
                ['name' => 'Marketer'],
                ['is_active' => true],
            )->id,
            'is_active' => true,
        ]);
    }

    private function addTeamMember(Team $team, Employee $employee): void
    {
        DB::table('team_members')->insert([
            'team_id' => $team->id,
            'employee_id' => $employee->id,
            'role' => 'member',
        ]);
    }

    private function makeClient(string $name, string $code): Client
    {
        return Client::create([
            'name' => $name,
            'code' => $code,
            'email' => strtolower($code).'@example.com',
            'phone' => '0'.random_int(100000000, 999999999),
            'is_active' => true,
            'password' => 'secret123',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeProject(string $businessName, array $overrides = []): Project
    {
        $project = Project::create(array_merge([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'client_id' => $this->client->id,
            'team_id' => $this->team->id,
            'assigned_employee_id' => $this->employee->id,
            'business_status' => BusinessStatus::CampaignRunning,
            'project_name' => $businessName,
            'business_name' => $businessName,
            'start_date' => '2026-01-01',
            'contract_months' => 12,
            'end_date' => '2027-01-01',
            'package_amount' => 1000,
            'amount_paid' => 0,
            'project_type' => 'regular',
            'health_status' => 'upcoming',
        ], $overrides));

        if (isset($overrides['health_status'])) {
            $project->updateQuietly(['health_status' => $overrides['health_status']]);
        }

        return $project->refresh();
    }

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/v1/admin/projects')->assertUnauthorized();
    }

    public function test_it_returns_a_paginator_the_datatable_can_render(): void
    {
        $this->makeProject('Acme Website');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'result' => [
                    'data' => [[
                        'id', 'business_name', 'company', 'department', 'client',
                        'team', 'assigned_employee', 'package_amount', 'amount_due',
                        'health_status', 'health_label', 'health_color',
                        'business_status', 'end_date', 'created_at',
                        'can_view_notes', 'can_add_notes',
                        'can_view_reports', 'can_submit_reports',
                    ]],
                    'links' => ['prev', 'next'],
                    'meta' => ['current_page', 'from', 'to', 'per_page'],
                ],
            ]);
    }

    public function test_it_searches_the_project_and_its_relations(): void
    {
        $this->makeProject('Acme Website');
        $this->makeProject('Globex Portal');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/v1/admin/projects?search=Acme Website')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.business_name', 'Acme Website');

        // Search spans the related client's name too, so both rows come back.
        $this->actingAs($user)
            ->getJson('/v1/admin/projects?search=Globex')
            ->assertOk()
            ->assertJsonCount(2, 'result.data');
    }

    public function test_it_filters_by_company_department_and_team(): void
    {
        $otherCompany = Company::create(['name' => 'Globex', 'code' => 'GBX', 'is_active' => true]);
        $otherDepartment = $this->makeDepartment('Support', 'SUP', $otherCompany);
        $otherTeam = $this->makeTeam('Bravo Team', $otherDepartment);

        $mine = $this->makeProject('Acme Website');
        $theirs = $this->makeProject('Globex Portal', [
            'company_id' => $otherCompany->id,
            'department_id' => $otherDepartment->id,
            'team_id' => $otherTeam->id,
            'assigned_employee_id' => null,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson("/v1/admin/projects?company_id={$this->company->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.id', $mine->id);

        $this->actingAs($user)
            ->getJson("/v1/admin/projects?department_id={$otherDepartment->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.id', $theirs->id);

        $this->actingAs($user)
            ->getJson("/v1/admin/projects?team_id={$this->team->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.id', $mine->id);
    }

    public function test_it_filters_by_client(): void
    {
        $otherClient = $this->makeClient('Initech', 'INI');

        $this->makeProject('Acme Website');
        $theirs = $this->makeProject('Initech Portal', ['client_id' => $otherClient->id]);

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/projects?client_id={$otherClient->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.id', $theirs->id);
    }

    public function test_it_filters_by_health_status(): void
    {
        $this->makeProject('Healthy One', ['health_status' => 'on_track']);
        $this->makeProject('Struggling One', ['health_status' => 'off_track']);

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/projects?health_status=off_track')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.business_name', 'Struggling One');
    }

    /** An unknown health value is now rejected up front instead of silently ignored. */
    public function test_an_invalid_health_status_is_rejected(): void
    {
        $this->makeProject('Acme Website');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/projects?health_status=not-a-status')
            ->assertStatus(422)
            ->assertJsonValidationErrors('health_status');
    }

    public function test_it_filters_by_business_status(): void
    {
        $this->makeProject('Acme Website');
        $this->makeProject('Paused Project', ['business_status' => BusinessStatus::OnHold]);

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/projects?business_status='.BusinessStatus::OnHold->value)
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.business_name', 'Paused Project');
    }

    public function test_it_sorts_by_a_whitelisted_column(): void
    {
        $this->makeProject('Zulu Project');
        $this->makeProject('Alpha Project');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/projects?sort=business_name&direction=asc')
            ->assertOk()
            ->assertJsonPath('result.data.0.business_name', 'Alpha Project');
    }

    public function test_it_honours_per_page(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->makeProject("Project {$i}");
        }

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/projects?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'result.data')
            ->assertJsonPath('result.meta.per_page', 2)
            ->assertJsonPath('result.meta.current_page', 1);
    }

    public function test_deleted_projects_drop_out_of_the_list(): void
    {
        $keep = $this->makeProject('Keep This');
        $drop = $this->makeProject('Drop This');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson("/v1/admin/projects/{$drop->id}")
            ->assertOk();

        // Soft delete — the row stays in the table with deleted_at stamped.
        $this->assertSoftDeleted('projects', ['id' => $drop->id]);

        $this->actingAs($user)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.id', $keep->id);
    }

    /**
     * A soft-deleted client takes its projects off the list with it — otherwise
     * the table keeps rendering rows with an empty client column. Restoring the
     * client brings them back, since nothing about the project itself changed.
     */
    public function test_a_soft_deleted_clients_projects_drop_out_of_the_list(): void
    {
        $keep = $this->makeProject('Keep This');

        $gone = $this->makeClient('Initech', 'INI');
        $this->makeProject('Cosmos Air Ticket', ['client_id' => $gone->id]);

        $gone->delete();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.id', $keep->id);

        $gone->restore();

        $this->actingAs($user)
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonCount(2, 'result.data');
    }

    // --- Searchable-select endpoints ---

    public function test_the_team_search_is_scoped_to_the_company_and_department(): void
    {
        $otherDepartment = $this->makeDepartment('Support', 'SUP');
        $this->makeTeam('Bravo Team', $otherDepartment);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson("/v1/admin/projects/teams/search?company_id={$this->company->id}&department_id={$this->department->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonFragment(['value' => $this->team->id, 'label' => 'Alpha Team']);

        // Without the department scope both teams of the company show up.
        $this->actingAs($user)
            ->getJson("/v1/admin/projects/teams/search?company_id={$this->company->id}")
            ->assertOk()
            ->assertJsonCount(2, 'result');

        $this->actingAs($user)
            ->getJson("/v1/admin/projects/teams/search?company_id={$this->company->id}&search=Bravo")
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonFragment(['label' => 'Bravo Team']);
    }

    public function test_the_employee_search_only_returns_members_of_the_team(): void
    {
        $outsider = $this->makeEmployee('Outsider Person', 'outsider');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson("/v1/admin/projects/employees/search?team_id={$this->team->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonFragment(['value' => $this->employee->id, 'label' => 'Rakib Hasan'])
            ->assertJsonMissing(['value' => $outsider->id]);

        // No team picked yet — the owner list stays empty.
        $this->actingAs($user)
            ->getJson('/v1/admin/projects/employees/search')
            ->assertOk()
            ->assertJsonCount(0, 'result');
    }

    public function test_the_client_search_returns_value_label_options(): void
    {
        $this->makeClient('Initech', 'INI');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/v1/admin/projects/clients/search')
            ->assertOk()
            ->assertJsonCount(2, 'result')
            ->assertJsonFragment(['value' => $this->client->id, 'label' => 'Globex Ltd']);

        $this->actingAs($user)
            ->getJson('/v1/admin/projects/clients/search?search=Initech')
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonFragment(['label' => 'Initech']);
    }

    public function test_the_client_search_carries_the_contact_details_the_form_prefills_from(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/projects/clients/search')
            ->assertOk()
            ->assertJsonFragment([
                'value' => $this->client->id,
                'label' => 'Globex Ltd',
                'email' => $this->client->email,
                'phone' => $this->client->phone,
            ]);
    }

    /**
     * The form's Company and Department pickers used to call the Company and
     * Department modules' own search endpoints, which sit behind `view
     * companies` / `view departments`. A staff member allowed to create a
     * project has neither, so both dropdowns came back empty.
     */
    public function test_the_project_pickers_only_need_the_projects_permission(): void
    {
        $role = Role::findOrCreate('project-staff', 'web');
        foreach (['view projects', 'create projects'] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $staff = User::factory()->create();
        $staff->assignRole($role);

        $this->actingAs($staff)
            ->getJson('/v1/admin/projects/companies/search')
            ->assertOk()
            ->assertJsonFragment(['value' => $this->company->id, 'label' => 'Acme Corp']);

        $this->actingAs($staff)
            ->getJson('/v1/admin/projects/departments/search')
            ->assertOk()
            ->assertJsonFragment(['value' => $this->department->id, 'label' => 'Engineering']);

        $this->actingAs($staff)
            ->getJson('/v1/admin/companies/search')
            ->assertForbidden();
    }

    public function test_the_project_department_search_stays_inside_the_picked_company(): void
    {
        $other = Company::create(['name' => 'Initech', 'code' => 'INI', 'is_active' => true]);
        $otherDepartment = $this->makeDepartment('Support', 'SUP', $other);

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/projects/departments/search?company_id={$this->company->id}")
            ->assertOk()
            ->assertJsonFragment(['value' => $this->department->id])
            ->assertJsonMissing(['value' => $otherDepartment->id]);
    }

    // --- Show ---

    public function test_show_returns_the_nested_milestones_sales_and_assignments(): void
    {
        $project = $this->makeProject('Challenge Project', [
            'project_type' => 'challenge_based',
            'sales_target' => 300,
            'target_start_date' => '2026-01-01',
            'target_months' => 3,
            'target_deadline' => '2026-04-01',
        ]);

        ProjectSale::create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'team_id' => $this->team->id,
            'employee_id' => $this->employee->id,
            'sale_date' => '2026-01-15',
            'amount' => 120,
            'reference' => 'INV-1',
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('result.business_name', 'Challenge Project')
            ->assertJsonPath('result.company', 'Acme Corp')
            ->assertJsonPath('result.department', 'Engineering')
            ->assertJsonPath('result.team', 'Alpha Team')
            ->assertJsonPath('result.client.name', 'Globex Ltd')
            ->assertJsonPath('result.assigned_employee.name', 'Rakib Hasan')
            ->assertJsonPath('result.business_status', BusinessStatus::CampaignRunning->value)
            ->assertJsonPath('result.business_status_label', 'Campaign Running')
            ->assertJsonPath('result.project_type_label', 'Challenge Based')
            ->assertJsonCount(3, 'result.milestones')
            ->assertJsonPath('result.milestones.0.sequence', 1)
            ->assertJsonCount(1, 'result.sales')
            ->assertJsonPath('result.sales.0.reference', 'INV-1')
            ->assertJsonPath('result.sales.0.employee', 'Rakib Hasan')
            ->assertJsonCount(1, 'result.assignments')
            ->assertJsonPath('result.assignments.0.employee', 'Rakib Hasan')
            ->assertJsonPath('result.assignments.0.reason', 'initial');
    }

    public function test_show_rejects_unauthenticated_requests(): void
    {
        $project = $this->makeProject('Acme Website');

        $this->getJson("/v1/admin/projects/{$project->id}")->assertUnauthorized();
    }
}
