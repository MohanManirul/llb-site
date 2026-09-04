<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers what Companies/Index.jsx relies on now that the page carries no
 * server props: the API has to do the searching, filtering, sorting and
 * paginating that the old Inertia controller did.
 */
class ApiCompanyIndexTest extends TestCase
{
    use RefreshDatabase;

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
        $this->getJson('/v1/admin/companies')->assertUnauthorized();
    }

    public function test_it_returns_a_paginator_the_datatable_can_render(): void
    {
        $this->makeCompany('Acme Corp');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/companies')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'result' => [
                    'data' => [['id', 'name', 'email', 'is_active']],
                    'links' => ['prev', 'next'],
                    'meta' => ['current_page', 'from', 'to', 'per_page'],
                ],
            ]);
    }

    public function test_it_searches_by_name_and_email(): void
    {
        $this->makeCompany('Acme Corp');
        $this->makeCompany('Globex');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/companies?search=Acme')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Acme Corp');
    }

    public function test_it_filters_by_status(): void
    {
        $this->makeCompany('Active Co');
        $this->makeCompany('Inactive Co', status: false);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/v1/admin/companies?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Inactive Co');

        $this->actingAs($user)
            ->getJson('/v1/admin/companies?is_active=1')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Active Co');
    }

    public function test_it_sorts_by_a_whitelisted_column(): void
    {
        $this->makeCompany('Zulu');
        $this->makeCompany('Alpha');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/companies?sort=name&direction=asc')
            ->assertOk()
            ->assertJsonPath('result.data.0.name', 'Alpha');
    }

    public function test_it_honours_per_page(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->makeCompany("Company {$i}");
        }

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/companies?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'result.data')
            ->assertJsonPath('result.meta.per_page', 2)
            ->assertJsonPath('result.meta.current_page', 1);
    }

    public function test_it_soft_deletes_a_company(): void
    {
        $keep = $this->makeCompany('Keep Co');
        $drop = $this->makeCompany('Drop One');

        $this->actingAs(User::factory()->create())
            ->deleteJson("/v1/admin/companies/{$drop->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        // Soft delete — the rows stay in the table with deleted_at stamped.
        $this->assertSoftDeleted('companies', ['id' => $drop->id]);
        $this->assertDatabaseHas('companies', ['id' => $keep->id, 'deleted_at' => null]);
    }

    public function test_deleted_companies_drop_out_of_the_list(): void
    {
        $keep = $this->makeCompany('Keep Co');
        $drop = $this->makeCompany('Drop Co');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson("/v1/admin/companies/{$drop->id}")
            ->assertOk();

        $this->actingAs($user)
            ->getJson('/v1/admin/companies')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.id', $keep->id);
    }

    /** A soft-deleted company must keep its logo, so a restore stays intact. */
    public function test_deleting_a_company_leaves_its_logo_on_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('companies/logo.png', 'fake');

        $company = $this->makeCompany('Logo Co');
        $company->update(['logo' => 'companies/logo.png']);

        $this->actingAs(User::factory()->create())
            ->deleteJson("/v1/admin/companies/{$company->id}")
            ->assertOk();

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
        Storage::disk('public')->assertExists('companies/logo.png');
    }
}
