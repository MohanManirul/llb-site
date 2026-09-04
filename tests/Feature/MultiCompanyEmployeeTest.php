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
use Tests\TestCase;

class MultiCompanyEmployeeTest extends TestCase
{
    use RefreshDatabase;

    private User $worker;

    private Employee $atAcme;

    private Employee $atGlobex;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->worker = User::factory()->create(['name' => 'Two Hat', 'email' => 'two@example.com']);

        $this->atAcme = $this->employAt('Acme Corp');
        $this->atGlobex = $this->employAt('Globex');

        $this->client = Client::create([
            'name' => 'Initech',
            'email' => 'initech@example.com',
            'phone' => '01700000001',
            'password' => 'secret123',
        ]);
    }

    private function employAt(string $companyName): Employee
    {
        $company = Company::create(['name' => $companyName]);
        $department = Department::create([
            'company_id' => $company->id,
            'name' => 'Sales',
        ]);
        Team::create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'name' => $companyName.' Team',
        ]);

        return Employee::create([
            'user_id' => $this->worker->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'designation_id' => Designation::firstOrCreate(['name' => 'Marketer'])->id,
            'is_active' => true,
        ]);
    }

    private function makeProject(string $name, Employee $assignee): Project
    {
        return Project::create([
            'company_id' => $assignee->company_id,
            'department_id' => $assignee->department_id,
            'team_id' => Team::where('company_id', $assignee->company_id)->value('id'),
            'client_id' => $this->client->id,
            'business_status' => BusinessStatus::CampaignRunning,
            'assigned_employee_id' => $assignee->id,
            'project_name' => $name,
            'business_name' => $name,
            'start_date' => '2026-01-01',
            'contract_months' => 12,
            'end_date' => '2027-01-01',
            'package_amount' => 1000,
            'amount_paid' => 250,
            'project_type' => 'regular',
            'health_status' => 'upcoming',
        ]);
    }

    public function test_both_employments_exist_and_are_listed(): void
    {
        $this->assertSame(2, $this->worker->employees()->count());

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/employees')
            ->assertOk();

        $this->assertEqualsCanonicalizing(
            ['Acme Corp', 'Globex'],
            collect($response->json('result.data'))->pluck('company_name')->all(),
        );
    }

    public function test_the_index_filter_narrows_to_one_employment(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/employees?company_id={$this->atAcme->company_id}")
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.company_name', 'Acme Corp');
    }

    public function test_projects_from_every_company_are_in_scope(): void
    {
        $this->makeProject('Acme Site', $this->atAcme);
        $this->makeProject('Globex Portal', $this->atGlobex);

        $stranger = User::factory()->create();
        $this->makeProject('Someone Else', $this->employOther($stranger));

        $response = $this->be($this->worker)
            ->getJson('/v1/admin/projects')
            ->assertOk();

        $this->assertEqualsCanonicalizing(
            ['Acme Site', 'Globex Portal'],
            collect($response->json('result.data'))->pluck('business_name')->all(),
        );
    }

    public function test_the_dashboard_sums_across_both_employments(): void
    {
        $this->makeProject('Acme Site', $this->atAcme);
        $this->makeProject('Globex Portal', $this->atGlobex);

        $response = $this->be($this->worker)
            ->getJson('/v1/admin/dashboard')
            ->assertOk();

        $this->assertCount(2, $response->json('result.sections.employee.assigned_projects'));

        $this->assertEqualsCanonicalizing(
            ['Acme Corp', 'Globex'],
            collect($response->json('result.sections.employee.employments'))->pluck('company')->all(),
        );
    }

    public function test_the_shared_roles_include_the_derived_employee_name(): void
    {
        $this->assertContains('employee', $this->worker->effectiveRoleNames()->all());
        $this->assertSame([], $this->worker->getRoleNames()->all());
    }

    private function employOther(User $user): Employee
    {
        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $this->atAcme->company_id,
            'department_id' => $this->atAcme->department_id,
            'designation_id' => $this->atAcme->designation_id,
            'is_active' => true,
        ]);
    }
}
