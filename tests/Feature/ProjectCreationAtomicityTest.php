<?php

namespace Tests\Feature;

use App\Actions\Project\GenerateProjectMilestonesAction;
use App\Enums\BusinessStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Team;
use App\Models\User;
use App\Services\Project\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Creating a project is not a single write: Project::create fires ProjectObserver,
 * which generates milestones and opens a ProjectAssignment. If any of those fail
 * the project row must not survive on its own — a project with no milestones and
 * no assignment is invisible to the dashboards that read them.
 */
class ProjectCreationAtomicityTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    private Team $team;

    private Employee $employee;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Acme Corp',
            'code' => 'ACME',
            'email' => 'acme@example.com',
            'is_active' => true,
        ]);

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

        $this->employee = Employee::create([
            'user_id' => User::factory()->create(['name' => 'Rakib Hasan'])->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => Designation::create(['name' => 'Marketer', 'is_active' => true])->id,
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'name' => 'Globex Ltd',
            'code' => 'GLX',
            'email' => 'glx@example.com',
            'phone' => '01700000001',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'team_id' => $this->team->id,
            'assigned_employee_id' => $this->employee->id,
            'business_status' => BusinessStatus::CampaignRunning,
            'project_name' => 'Acme Website',
            'business_name' => 'Acme Website',
            'start_date' => '2026-01-01',
            'contract_months' => 12,
            'package_amount' => 1000,
            'project_type' => 'regular',
        ];
    }

    public function test_it_creates_the_project_with_its_assignment(): void
    {
        $project = app(ProjectService::class)->create($this->payload());

        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseHas('project_assignments', [
            'project_id' => $project->id,
            'employee_id' => $this->employee->id,
        ]);
    }

    public function test_a_failure_inside_the_observer_leaves_no_project_behind(): void
    {
        $this->app->bind(
            GenerateProjectMilestonesAction::class,
            fn () => throw new RuntimeException('milestone generation failed'),
        );

        try {
            app(ProjectService::class)->create($this->payload());
            $this->fail('Expected the milestone failure to propagate out of create().');
        } catch (RuntimeException $e) {
            $this->assertSame('milestone generation failed', $e->getMessage());
        }

        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('project_assignments', 0);
    }
}
