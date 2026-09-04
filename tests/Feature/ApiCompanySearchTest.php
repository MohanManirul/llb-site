<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiCompanySearchTest extends TestCase
{
    use RefreshDatabase;

    /** No CompanyFactory exists yet, so build rows the way the other tests do. */
    private function makeCompany(string $name, bool $status = true): Company
    {
        return Company::create([
            'name' => $name,
            'code' => str_replace(' ', '', $name),
            'email' => str_replace(' ', '', strtolower($name)).'@example.com',
            'is_active' => $status,
        ]);
    }

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/v1/admin/companies/search')
            ->assertUnauthorized();
    }

    /**
     * The Inertia frontend calls /v1/admin/* with nothing but the session cookie,
     * which only works because bootstrap/app.php enables statefulApi().
     */
    public function test_a_session_authenticated_user_can_reach_the_api(): void
    {
        $this->makeCompany('Acme Corp');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/companies/search')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('result.0.label', 'Acme Corp');
    }

    public function test_a_bearer_token_user_can_reach_the_api(): void
    {
        $this->makeCompany('Acme Corp');

        Sanctum::actingAs($this->grantFullAccess(User::factory()->create()));

        $this->getJson('/v1/admin/companies/search')
            ->assertOk()
            ->assertJsonPath('result.0.label', 'Acme Corp');
    }

    public function test_it_filters_by_search_term_and_hides_inactive_companies(): void
    {
        $this->makeCompany('Acme Corp');
        $this->makeCompany('Globex');
        $this->makeCompany('Acme Hidden', status: false);

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/companies/search?search=Acme')
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.label', 'Acme Corp');
    }

    /**
     * Guards the route order in api.php: "search" must not be captured
     * by the {company} wildcard on GET /companies/{company}.
     */
    public function test_the_search_route_is_not_shadowed_by_the_resource_wildcard(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/companies/search')
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'result']);
    }
}
