<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers what Departments/Index.jsx relies on now that the page carries no
 * server props: the API has to do the searching, filtering, sorting and
 * paginating that the old Inertia controller did.
 */
class ApiDepartmentIndexTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompany(string $name): Company
    {
        return Company::create([
            'name' => $name,
            'code' => str_replace(' ', '', $name),
            'email' => str_replace(' ', '', strtolower($name)).'@example.com',
            'is_active' => true,
        ]);
    }

    private function makeDepartment(
        string $name,
        ?Company $company = null,
        bool $status = true,
        ?string $description = null,
    ): Department {
        $company ??= $this->makeCompany('Default Co '.uniqid());

        return Department::create([
            'company_id' => $company->id,
            'name' => $name,
            // Codes are globally unique, so keep them derived from the name.
            'code' => strtoupper(str_replace(' ', '', $name)),
            'description' => $description,
            'is_active' => $status,
        ]);
    }

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/v1/admin/departments')->assertUnauthorized();
    }

    public function test_it_returns_a_paginator_the_datatable_can_render(): void
    {
        $this->makeDepartment('Engineering');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/departments')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'result' => [
                    'data' => [[
                        'id', 'company_id', 'company_name', 'name',
                        'description', 'is_active', 'created_at',
                    ]],
                    'links' => ['prev', 'next'],
                    'meta' => ['current_page', 'from', 'to', 'per_page'],
                ],
            ]);
    }

    /**
     * DepartmentForm hydrates the company SearchableSelect from the show
     * response. Without the logo the trigger falls back to the placeholder
     * icon, so the picked company looks like it has no image.
     */
    public function test_the_show_response_carries_the_company_logo_for_the_select_avatar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('companies/acme.png', 'fake-bytes');

        $company = $this->makeCompany('Acme Corp');
        $company->update(['logo' => 'companies/acme.png']);

        $department = $this->makeDepartment('Engineering', $company);

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/departments/'.$department->id)
            ->assertOk()
            ->assertJsonPath('result.company_name', 'Acme Corp')
            ->assertJsonPath('result.company_logo_url', Storage::disk('public')->url('companies/acme.png'))
            ->assertJsonPath('result.company_thumbnail_url', Storage::disk('public')->url('companies/acme.png'));
    }

    public function test_it_searches_by_name_code_description_and_company_name(): void
    {
        $acme = $this->makeCompany('Acme Corp');
        $globex = $this->makeCompany('Globex');

        $this->makeDepartment('Engineering', $acme, description: 'Builds things');
        $this->makeDepartment('Marketing', $globex, description: 'Sells things');

        $user = User::factory()->create();

        // name
        $this->actingAs($user)
            ->getJson('/v1/admin/departments?search=Engine')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Engineering');

        // code
        $this->actingAs($user)
            ->getJson('/v1/admin/departments?search=MARKETING')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Marketing');

        // description
        $this->actingAs($user)
            ->getJson('/v1/admin/departments?search=Builds')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Engineering');

        // related company name
        $this->actingAs($user)
            ->getJson('/v1/admin/departments?search=Globex')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Marketing');
    }

    public function test_it_filters_by_status(): void
    {
        $this->makeDepartment('Active Dept');
        $this->makeDepartment('Inactive Dept', status: false);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/v1/admin/departments?is_active=active')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Active Dept');

        $this->actingAs($user)
            ->getJson('/v1/admin/departments?is_active=inactive')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Inactive Dept');

        // An empty status means "All Status" — nothing is filtered out.
        $this->actingAs($user)
            ->getJson('/v1/admin/departments?is_active=')
            ->assertOk()
            ->assertJsonCount(2, 'result.data');
    }

    public function test_it_filters_by_company(): void
    {
        $acme = $this->makeCompany('Acme Corp');
        $globex = $this->makeCompany('Globex');

        $this->makeDepartment('Engineering', $acme);
        $this->makeDepartment('Marketing', $globex);

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/departments?company_id='.$acme->id)
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Engineering')
            ->assertJsonPath('result.data.0.company_name', 'Acme Corp');
    }

    public function test_it_sorts_by_a_whitelisted_column(): void
    {
        $this->makeDepartment('Zulu');
        $this->makeDepartment('Alpha');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/departments?sort=name&direction=asc')
            ->assertOk()
            ->assertJsonPath('result.data.0.name', 'Alpha');
    }

    public function test_it_honours_per_page(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->makeDepartment("Dept {$i}");
        }

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/departments?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'result.data')
            ->assertJsonPath('result.meta.per_page', 2)
            ->assertJsonPath('result.meta.current_page', 1);
    }

    public function test_it_soft_deletes_a_department(): void
    {
        $keep = $this->makeDepartment('Keep Dept');
        $drop = $this->makeDepartment('Drop One');

        $this->actingAs(User::factory()->create())
            ->deleteJson("/v1/admin/departments/{$drop->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        // Soft delete only — the rows stay behind with a deleted_at stamp.
        $this->assertSoftDeleted('departments', ['id' => $drop->id]);
        $this->assertDatabaseHas('departments', [
            'id' => $keep->id,
            'deleted_at' => null,
        ]);
    }

    /** The option list behind the company-scoped SearchableSelect. */
    public function test_search_returns_options_scoped_to_a_company(): void
    {
        $acme = $this->makeCompany('Acme Corp');
        $globex = $this->makeCompany('Globex');

        $this->makeDepartment('Engineering', $acme);
        $this->makeDepartment('Marketing', $globex);
        $this->makeDepartment('Hidden Dept', $acme, status: false);

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/departments/search?company_id='.$acme->id)
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.label', 'Engineering');
    }
}
