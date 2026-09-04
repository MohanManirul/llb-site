<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers what Teams/Index.jsx (and Show.jsx) rely on now that those pages carry
 * no server props: the API has to do the searching, filtering, sorting and
 * paginating the old Inertia controller did, plus the option lists that feed
 * the filter dropdowns.
 */
class ApiTeamIndexTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Acme Corp',
            'code' => 'ACME',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Engineering',
            'code' => 'ENG',
            'is_active' => true,
        ]);
    }

    private function makeTeam(string $name, bool $status = true, ?Company $company = null, ?Department $department = null): Team
    {
        return Team::create([
            'company_id' => ($company ?? $this->company)->id,
            'department_id' => ($department ?? $this->department)->id,
            'name' => $name,
            'is_active' => $status,
        ]);
    }

    /** Identity lives on `users`, so an employee always needs a user first. */
    private function makeEmployee(string $name): Employee
    {
        $slug = strtolower(str_replace(' ', '', $name));

        $user = User::factory()->create([
            'name' => $name,
            'email' => "{$slug}@example.com",
            'image' => "users/{$slug}.jpg",
        ]);

        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => Designation::firstOrCreate(
                ['name' => 'Engineer'],
                ['is_active' => true],
            )->id,
            'is_active' => true,
        ]);
    }

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/v1/admin/teams')->assertUnauthorized();
    }

    public function test_it_returns_a_paginator_the_datatable_can_render(): void
    {
        $this->makeTeam('Platform');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/teams')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'result' => [
                    'data' => [[
                        'id', 'company_id', 'company_name', 'department_id',
                        'department_name', 'name', 'leader', 'members_count',
                        'members', 'is_active', 'created_at',
                    ]],
                    'links' => ['prev', 'next'],
                    'meta' => ['current_page', 'from', 'to', 'per_page'],
                ],
            ]);
    }

    public function test_it_searches_by_team_company_department_and_leader_name(): void
    {
        $platform = $this->makeTeam('Platform');
        $this->makeTeam('Design');

        $leader = $this->makeEmployee('Ada Lovelace');
        $platform->members()->attach([$leader->id => ['role' => 'leader']]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/v1/admin/teams?search=Platform')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Platform');

        // leaders.name → whereHas on the pivot-scoped relation
        $this->actingAs($user)
            ->getJson('/v1/admin/teams?search=Ada')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Platform');

        // company.name / department.name both match every team here
        $this->actingAs($user)
            ->getJson('/v1/admin/teams?search=Engineering')
            ->assertOk()
            ->assertJsonCount(2, 'result.data');
    }

    public function test_it_filters_by_status(): void
    {
        $this->makeTeam('Active Team');
        $this->makeTeam('Inactive Team', status: false);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/v1/admin/teams?is_active=1')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Active Team');

        $this->actingAs($user)
            ->getJson('/v1/admin/teams?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Inactive Team');
    }

    public function test_it_filters_by_team_id(): void
    {
        $platform = $this->makeTeam('Platform');
        $this->makeTeam('Design');

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/teams?team_id={$platform->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.id', $platform->id);
    }

    public function test_it_filters_by_company_and_department(): void
    {
        $this->makeTeam('Platform');

        $otherCompany = Company::create(['name' => 'Globex', 'code' => 'GLX', 'is_active' => true]);
        $otherDepartment = Department::create([
            'company_id' => $otherCompany->id,
            'name' => 'Sales',
            'code' => 'SAL',
            'is_active' => true,
        ]);
        $this->makeTeam('Field Sales', company: $otherCompany, department: $otherDepartment);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson("/v1/admin/teams?company_id={$this->company->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Platform');

        $this->actingAs($user)
            ->getJson("/v1/admin/teams?department_id={$otherDepartment->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Field Sales');
    }

    public function test_it_filters_by_role_and_by_a_specific_employee(): void
    {
        $withLeader = $this->makeTeam('Platform');
        $withMemberOnly = $this->makeTeam('Design');

        $leader = $this->makeEmployee('Ada Lovelace');
        $member = $this->makeEmployee('Grace Hopper');

        $withLeader->members()->attach([$leader->id => ['role' => 'leader']]);
        $withMemberOnly->members()->attach([$member->id => ['role' => 'member']]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/v1/admin/teams?role=leader')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Platform');

        $this->actingAs($user)
            ->getJson('/v1/admin/teams?role=member')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Design');

        $this->actingAs($user)
            ->getJson("/v1/admin/teams?role=leader&employee_id={$leader->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Platform');

        $this->actingAs($user)
            ->getJson("/v1/admin/teams?employee_id={$member->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Design');
    }

    public function test_it_sorts_by_a_whitelisted_column(): void
    {
        $this->makeTeam('Zulu');
        $this->makeTeam('Alpha');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/v1/admin/teams?sort=name&direction=asc')
            ->assertOk()
            ->assertJsonPath('result.data.0.name', 'Alpha');

        $this->actingAs($user)
            ->getJson('/v1/admin/teams?sort=name&direction=desc')
            ->assertOk()
            ->assertJsonPath('result.data.0.name', 'Zulu');
    }

    /**
     * Company/Department are related tables, so they are sorted through a
     * selectSub alias. The whitelist has to carry the SAME key the table sends
     * (`company_name`), or Sortable silently drops it and falls back to the
     * default order — a sort that looks alive but does nothing.
     */
    public function test_it_sorts_by_the_related_company_and_department_name(): void
    {
        $zulu = Company::create([
            'name' => 'Zulu Corp', 'code' => 'ZULU',
            'email' => 'zulu@example.com', 'is_active' => true,
        ]);
        $zuluDepartment = Department::create([
            'company_id' => $zulu->id, 'name' => 'Zulu Dept',
            'code' => 'ZDEP', 'is_active' => true,
        ]);

        // Team B is created FIRST and belongs to the alphabetically LAST
        // company. The default order (created_at desc) would therefore put
        // Team A on top — so if the sort is silently ignored this test fails,
        // which is the whole point of it.
        $this->makeTeam('Team B', company: $zulu, department: $zuluDepartment);
        $this->travel(1)->second();
        $this->makeTeam('Team A');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/v1/admin/teams')
            ->assertOk()
            ->assertJsonPath('result.data.0.name', 'Team A');

        $this->actingAs($user)
            ->getJson('/v1/admin/teams?sort=company_name&direction=desc')
            ->assertOk()
            ->assertJsonPath('result.data.0.name', 'Team B');

        $this->actingAs($user)
            ->getJson('/v1/admin/teams?sort=department_name&direction=desc')
            ->assertOk()
            ->assertJsonPath('result.data.0.name', 'Team B');
    }

    public function test_it_honours_per_page(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->makeTeam("Team {$i}");
        }

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/teams?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'result.data')
            ->assertJsonPath('result.meta.per_page', 2)
            ->assertJsonPath('result.meta.current_page', 1);
    }

    public function test_it_soft_deletes_a_team(): void
    {
        $keep = $this->makeTeam('Keep');
        $drop = $this->makeTeam('Drop One');

        $this->actingAs(User::factory()->create())
            ->deleteJson("/v1/admin/teams/{$drop->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('teams', ['id' => $drop->id]);
        $this->assertNotSoftDeleted('teams', ['id' => $keep->id]);
    }

    public function test_the_team_search_endpoint_returns_options(): void
    {
        $platform = $this->makeTeam('Platform');
        $this->makeTeam('Design');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/teams/search?search=Plat')
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.value', $platform->id)
            ->assertJsonPath('result.0.label', 'Platform');
    }

    public function test_the_member_search_endpoint_is_scoped_by_role(): void
    {
        $team = $this->makeTeam('Platform');

        $leader = $this->makeEmployee('Ada Lovelace');
        $member = $this->makeEmployee('Grace Hopper');
        $this->makeEmployee('Alan Turing');

        $team->members()->attach([
            $leader->id => ['role' => 'leader'],
            $member->id => ['role' => 'member'],
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/v1/admin/teams/members/search?role=leader')
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.label', 'Ada Lovelace');

        $this->actingAs($user)
            ->getJson('/v1/admin/teams/members/search?role=member')
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.label', 'Grace Hopper');

        $this->actingAs($user)
            ->getJson('/v1/admin/teams/members/search')
            ->assertOk()
            ->assertJsonCount(2, 'result')
            ->assertJsonMissing(['label' => 'Alan Turing']);
    }

    public function test_the_show_endpoint_returns_the_members_the_show_page_needs(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('users/adalovelace.jpg', 'fake-bytes');

        $team = $this->makeTeam('Platform');

        $leader = $this->makeEmployee('Ada Lovelace');
        $member = $this->makeEmployee('Grace Hopper');

        $team->members()->attach([
            $leader->id => ['role' => 'leader'],
            $member->id => ['role' => 'member'],
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/teams/{$team->id}")
            ->assertOk()
            ->assertJsonPath('result.name', 'Platform')
            ->assertJsonPath('result.company_name', 'Acme Corp')
            ->assertJsonPath('result.department_name', 'Engineering')
            ->assertJsonCount(2, 'result.members')
            ->assertJsonPath('result.members.0.role', 'leader')
            ->assertJsonPath('result.members.0.name', 'Ada Lovelace')
            ->assertJsonPath('result.members.0.thumbnail_url', Storage::disk('public')->url('users/adalovelace.jpg'))
            ->assertJsonPath('result.members.1.role', 'member');
    }
}
