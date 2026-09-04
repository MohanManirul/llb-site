<?php

namespace Tests\Feature;

use App\Enums\BusinessStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientPortalLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super-admin', 'web');
    }

    public function test_creating_a_client_with_password_grants_the_portal_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/clients', [
                'name' => 'Acme Corp',
                'email' => 'acme@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'phone' => '01711112222',
                'is_active' => true,
            ])
            ->assertCreated();

        $client = Client::where('email', 'acme@example.com')->first();
        $this->assertNotNull($client);
        $this->assertNotNull($client->password);
        $this->assertNull(User::where('email', 'acme@example.com')->first());

        $this->postJson('/v1/client/login', [
            'email' => 'acme@example.com',
            'password' => 'secret123',
        ])
            ->assertOk()
            ->assertJsonPath('result.client.id', $client->id)
            ->assertJsonPath('result.token_type', 'Bearer')
            ->assertJsonStructure(['result' => ['token']]);
    }

    public function test_creating_a_client_requires_a_confirmed_password(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/clients', [
                'name' => 'Acme Corp',
                'email' => 'acme@example.com',
                'phone' => '01711112222',
                'is_active' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/clients', [
                'name' => 'Acme Corp',
                'email' => 'acme@example.com',
                'phone' => '01711112222',
                'is_active' => true,
                'password' => 'secret123',
                'password_confirmation' => 'mismatch',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertNull(Client::where('email', 'acme@example.com')->first());
    }

    public function test_an_inactive_client_cannot_sign_in(): void
    {
        Client::create([
            'name' => 'Portal Client',
            'email' => 'portal@example.com',
            'password' => 'secret123',
            'phone' => '01733334444',
            'is_active' => false,
        ]);

        $this->postJson('/v1/client/login', [
            'email' => 'portal@example.com',
            'password' => 'secret123',
        ])->assertStatus(422);
    }

    public function test_client_dashboard_only_shows_their_own_section(): void
    {
        $client = $this->portalClient();

        Client::create([
            'name' => 'Other',
            'email' => 'other@example.com',
            'phone' => '01755556666',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        $this->actingAs($client, 'client')
            ->getJson('/v1/client/dashboard')
            ->assertOk()
            ->assertJsonPath('result.sections.client.profile.id', $client->id)
            ->assertJsonPath('result.sections.client.totals.projects', 0)
            ->assertJsonMissingPath('result.sections.overview');

        $this->actingAs($client, 'client')
            ->getJson('/v1/client/dashboard/report')
            ->assertOk()
            ->assertJsonPath('result.heading', 'My account — Portal Client');
    }

    public function test_client_profile_returns_the_client_itself(): void
    {
        $client = $this->portalClient();

        $this->actingAs($client, 'client')
            ->getJson('/v1/profile')
            ->assertOk()
            ->assertJsonPath('result.user.id', $client->id)
            ->assertJsonPath('result.user.email', 'portal@example.com');
    }

    public function test_a_client_token_is_rejected_by_the_staff_guard(): void
    {
        $client = $this->portalClient();
        $token = $client->createToken('client-api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/v1/admin/clients')
            ->assertUnauthorized();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/v1/admin/users')
            ->assertUnauthorized();
    }

    public function test_a_client_can_sign_in_at_the_web_login(): void
    {
        $client = $this->portalClient();

        $this->post('/login', [
            'email' => 'portal@example.com',
            'password' => 'secret123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($client, 'client-web');
        $this->assertGuest('web');

        $this->getJson('/v1/client/dashboard/report')
            ->assertOk()
            ->assertJsonPath('result.heading', 'My account — Portal Client');
    }

    public function test_remember_me_issues_a_recaller_cookie_for_a_client(): void
    {
        $client = $this->portalClient();

        $response = $this->post('/login', [
            'email' => 'portal@example.com',
            'password' => 'secret123',
            'remember' => true,
        ])->assertRedirect('/dashboard');

        $response->assertCookie(Auth::guard('client-web')->getRecallerName());
        $this->assertNotNull($client->fresh()->remember_token);
    }

    public function test_an_inactive_client_cannot_sign_in_at_the_web_login(): void
    {
        $client = $this->portalClient();
        $client->update(['is_active' => false]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'portal@example.com',
                'password' => 'secret123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest('client-web');
    }

    public function test_a_signed_in_client_cannot_open_the_staff_pages(): void
    {
        $client = $this->portalClient();

        $this->actingAs($client, 'client-web')
            ->get('/admin/clients')
            ->assertRedirect('/admin/login');

        $this->actingAs($client, 'client-web')
            ->getJson('/v1/admin/clients')
            ->assertUnauthorized();
    }

    public function test_a_guest_is_sent_to_the_client_login_not_the_admin_one(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_a_signed_in_client_can_open_their_portal_pages(): void
    {
        $client = $this->portalClient();
        $project = $this->project($client, 'Own Biz');

        $this->actingAs($client, 'client-web')
            ->get('/dashboard')
            ->assertOk();

        $this->actingAs($client, 'client-web')
            ->get('/profile')
            ->assertOk();

        $this->actingAs($client, 'client-web')
            ->get("/projects/{$project->id}")
            ->assertOk();
    }

    public function test_a_signed_in_client_is_sent_off_the_client_login(): void
    {
        $client = $this->portalClient();

        $this->actingAs($client, 'client-web')
            ->get('/login')
            ->assertRedirect('/dashboard');
    }

    public function test_a_staff_user_cannot_open_the_client_portal(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_a_client_can_sign_out_of_the_web_session(): void
    {
        $client = $this->portalClient();

        $this->actingAs($client, 'client-web')
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest('client-web');
    }

    public function test_client_project_endpoints_are_scoped_to_their_own_projects(): void
    {
        $client = $this->portalClient();

        $other = Client::create([
            'name' => 'Other',
            'email' => 'other@example.com',
            'phone' => '01722222222',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        $own = $this->project($client, 'Own Biz');
        $foreign = $this->project($other, 'Foreign Biz');

        $this->actingAs($client, 'client')
            ->getJson("/v1/client/projects/{$own->id}")
            ->assertOk()
            ->assertJsonPath('result.business_name', 'Own Biz');

        $this->actingAs($client, 'client')
            ->getJson("/v1/client/projects/{$foreign->id}")
            ->assertForbidden();

        $this->actingAs($client, 'client')
            ->getJson('/v1/client/projects')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.business_name', 'Own Biz');
    }

    private function portalClient(): Client
    {
        return Client::create([
            'name' => 'Portal Client',
            'email' => 'portal@example.com',
            'password' => 'secret123',
            'phone' => '01733334444',
            'is_active' => true,
        ]);
    }

    private function project(Client $client, string $name): Project
    {
        $company = Company::firstOrCreate(['name' => 'Acme'], ['is_active' => true]);
        $department = Department::firstOrCreate(
            ['name' => 'Sales'],
            ['company_id' => $company->id, 'is_active' => true],
        );
        $team = Team::firstOrCreate(
            ['name' => 'Alpha'],
            ['company_id' => $company->id, 'department_id' => $department->id, 'is_active' => true],
        );
        $marketer = User::firstOrCreate(
            ['email' => 'm@example.com'],
            ['name' => 'Marketer', 'password' => 'password'],
        );
        $employee = Employee::firstOrCreate(
            ['user_id' => $marketer->id],
            [
                'company_id' => $company->id,
                'department_id' => $department->id,
                'designation_id' => Designation::firstOrCreate(['name' => 'Marketer'], ['is_active' => true])->id,
                'is_active' => true,
            ],
        );

        return Project::create([
            'client_id' => $client->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'team_id' => $team->id,
            'assigned_employee_id' => $employee->id,
            'business_status' => BusinessStatus::CampaignRunning,
            'project_name' => $name,
            'business_name' => $name,
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
}
