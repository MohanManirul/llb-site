<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_returns_departments_for_the_selected_company(): void
    {
        $this->actingAs(User::factory()->create());

        $companyA = Company::create(['name' => 'Company A', 'code' => 'A', 'is_active' => true]);
        $companyB = Company::create(['name' => 'Company B', 'code' => 'B', 'is_active' => true]);

        Department::create(['company_id' => $companyA->id, 'name' => 'Engineering', 'code' => 'ENG', 'is_active' => true]);
        Department::create(['company_id' => $companyA->id, 'name' => 'HR', 'code' => 'HR', 'is_active' => true]);
        Department::create(['company_id' => $companyB->id, 'name' => 'Sales', 'code' => 'SAL', 'is_active' => true]);

        // The option list moved to the API; the ApiResponse facade wraps it in `result`.
        $response = $this->getJson(route('v1.admin.departments.search', ['company_id' => $companyA->id]));

        $response->assertOk();
        $response->assertJsonCount(2, 'result');
        $response->assertJsonFragment(['label' => 'Engineering']);
        $response->assertJsonMissing(['label' => 'Sales']);
    }
}
