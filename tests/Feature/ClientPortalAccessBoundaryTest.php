<?php

namespace Tests\Feature;

use App\Enums\BusinessStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A portal client shares /v1/admin/dashboard, /v1/admin/dashboard/report and
 * /v1/client/projects with staff. Every one of those has to hand back the client's
 * own data only — never a company-wide total and never another client's row.
 */
class ClientPortalAccessBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    private Team $team;

    private Client $client;

    private Client $otherClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Acme Corp', 'code' => 'ACME', 'is_active' => true]);
        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Engineering',
            'code' => 'ENG',
            'is_active' => true,
        ]);
        $this->team = Team::create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'name' => 'Alpha Team',
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'name' => 'Globex Ltd',
            'code' => 'GLX',
            'email' => 'glx@example.com',
            'phone' => '01700000001',
            'is_active' => true,
            'is_active' => true,
            'password' => 'portal-password',
        ]);

        $this->otherClient = Client::create([
            'name' => 'Initech',
            'code' => 'INI',
            'email' => 'initech@example.com',
            'phone' => '01700000002',
            'is_active' => true,
            'is_active' => true,
            'password' => 'secret123',
        ]);
    }

    private function makeProject(string $businessName, Client $client): Project
    {
        return Project::create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'client_id' => $client->id,
            'team_id' => $this->team->id,
            'business_status' => BusinessStatus::CampaignRunning,
            'project_name' => $businessName,
            'business_name' => $businessName,
            'start_date' => '2026-01-01',
            'contract_months' => 12,
            'end_date' => '2027-01-01',
            'package_amount' => 1000,
            'amount_paid' => 250,
            'project_type' => 'regular',
            'health_status' => 'upcoming',
        ]);
    }

    public function test_the_report_gives_a_client_no_company_wide_totals(): void
    {
        $this->makeProject('Globex Site', $this->client);
        $this->makeProject('Initech Site', $this->otherClient);

        $response = $this->actingAs($this->client, 'client-web')
            ->getJson('/v1/client/dashboard/report')
            ->assertOk();

        $cards = collect($response->json('result.cards'))->pluck('value', 'label');

        $this->assertArrayNotHasKey('Total Clients', $cards->all());
        $this->assertArrayNotHasKey('New Clients', $cards->all());
        $this->assertArrayNotHasKey('Employees', $cards->all());
        $this->assertArrayNotHasKey('Teams', $cards->all());

        $this->assertSame('My account — Globex Ltd', $response->json('result.heading'));
        $this->assertSame('1', $cards['My Projects']);
        $this->assertNull($response->json('result.recent'));
    }

    public function test_the_overview_gives_a_client_only_their_own_section(): void
    {
        $this->makeProject('Globex Site', $this->client);
        $this->makeProject('Initech Site', $this->otherClient);

        $response = $this->actingAs($this->client, 'client-web')
            ->getJson('/v1/client/dashboard')
            ->assertOk();

        $this->assertSame(['client'], array_keys($response->json('result.sections')));
        $this->assertNull($response->json('result.sections.overview'));
        $this->assertNull($response->json('result.sections.finance'));

        $this->assertSame(1, $response->json('result.sections.client.totals.projects'));
        $this->assertSame(
            ['Globex Site'],
            array_column($response->json('result.sections.client.projects'), 'business_name'),
        );
    }

    public function test_a_client_only_lists_their_own_projects(): void
    {
        $this->makeProject('Globex Site', $this->client);
        $this->makeProject('Initech Site', $this->otherClient);

        $response = $this->actingAs($this->client, 'client-web')
            ->getJson('/v1/client/projects')
            ->assertOk();

        $this->assertSame(
            ['Globex Site'],
            array_column($response->json('result.data'), 'business_name'),
        );
    }

    public function test_a_client_cannot_open_another_clients_project(): void
    {
        $own = $this->makeProject('Globex Site', $this->client);
        $foreign = $this->makeProject('Initech Site', $this->otherClient);

        $this->actingAs($this->client, 'client-web')
            ->getJson("/v1/client/projects/{$own->id}")
            ->assertOk();

        $this->actingAs($this->client, 'client-web')
            ->getJson("/v1/client/projects/{$foreign->id}")
            ->assertForbidden();
    }

    public function test_a_client_cannot_reach_the_staff_only_endpoints(): void
    {
        $endpoints = [
            '/v1/admin/clients', '/v1/admin/users', '/v1/admin/employees', '/v1/admin/teams', '/v1/admin/companies',
            '/v1/admin/dashboard', '/v1/admin/dashboard/report',
        ];

        foreach ($endpoints as $endpoint) {
            $this->actingAs($this->client, 'client-web')
                ->getJson($endpoint)
                ->assertUnauthorized();
        }
    }

    /**
     * The `client` guard runs on Sanctum's driver and config/sanctum.php lists
     * `web` in its chain, so a staff session passes `auth:client`. The audience
     * middleware is what actually keeps staff out of the portal endpoints.
     */
    public function test_a_staff_session_cannot_reach_the_client_endpoints(): void
    {
        $staff = User::factory()->create();

        foreach (['/v1/client/me', '/v1/client/dashboard', '/v1/client/dashboard/report'] as $endpoint) {
            $this->actingAs($staff)
                ->getJson($endpoint)
                ->assertForbidden();
        }
    }

    public function test_the_portal_pages_stay_closed_to_a_staff_session(): void
    {
        $staff = User::factory()->create();

        foreach (['/dashboard', '/projects', '/profile'] as $page) {
            $this->actingAs($staff)->get($page)->assertRedirect('/login');
        }
    }

    public function test_the_shared_profile_endpoint_returns_the_client_not_a_staff_account(): void
    {
        User::factory()->create(['name' => 'Staff Person', 'email' => 'staff@example.com']);

        $this->actingAs($this->client, 'client-web')
            ->getJson('/v1/profile')
            ->assertOk()
            ->assertJsonPath('result.user.email', 'glx@example.com');
    }
}
