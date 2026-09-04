<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_more_than_ten_active_companies_when_searching_without_a_term(): void
    {
        $this->actingAs(User::factory()->create());

        for ($i = 1; $i <= 11; $i++) {
            Company::create([
                'name' => "Company {$i}",
                'code' => "CMP{$i}",
                'email' => "company{$i}@example.com",
                'is_active' => true,
            ]);
        }

        // The web route is gone — the picker now calls the API directly.
        $response = $this->getJson(route('v1.admin.companies.search'));

        $response->assertOk();
        $response->assertJsonFragment(['label' => 'Company 11']);
    }
}
