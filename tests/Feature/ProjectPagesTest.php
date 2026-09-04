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
 * The Projects web routes are page shells only — no data, no controller.
 * These tests pin the two things that can still break: the pages render, and
 * `auth:sanctum` still redirects a browser guest to /login rather than
 * answering with a 401.
 */
class ProjectPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_project_pages_render_without_server_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/projects')->assertOk();

        // /projects/create must not be swallowed by the /projects/{project}
        // show route — the registration order guards that.
        $this->actingAs($user)->get('/admin/projects/create')->assertOk();

        // Show still only needs the id from the URL; the record itself is
        // fetched from the API, so an unknown id renders the shell anyway.
        $this->actingAs($user)->get('/admin/projects/7')->assertOk();

        // Edit is the exception: it has to load the project to answer whether
        // this user may edit *this* one, so an unknown id is a 404 now.
        $this->actingAs($user)->get("/admin/projects/{$this->makeProject()->id}/edit")->assertOk();
        $this->actingAs($user)->get('/admin/projects/7/edit')->assertNotFound();
    }

    private function makeProject(): Project
    {
        $company = Company::create(['name' => 'Acme', 'code' => 'ACME', 'is_active' => true]);
        $department = Department::create([
            'company_id' => $company->id,
            'name' => 'Sales',
            'code' => 'SAL',
            'is_active' => true,
        ]);
        $team = Team::create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'name' => 'Alpha',
            'is_active' => true,
        ]);
        $client = Client::create([
            'name' => 'Globex',
            'code' => 'GLX',
            'email' => 'globex@example.com',
            'phone' => '01711111111',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        return Project::create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'team_id' => $team->id,
            'client_id' => $client->id,
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
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/projects')->assertRedirect('/admin/login');
        $this->get('/admin/projects/create')->assertRedirect('/admin/login');
        $this->get('/admin/projects/7')->assertRedirect('/admin/login');
    }
}
