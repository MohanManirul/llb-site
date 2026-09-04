<?php

namespace Tests\Feature;

use App\DTOs\MilestoneProgress;
use App\Enums\BusinessStatus;
use App\Enums\HealthStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Project;
use App\Models\SalesReport;
use App\Models\Team;
use App\Models\User;
use App\Services\Project\MilestoneProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sales progress is measured against the 1-month milestone the project is in
 * today, anchored on the project's start date — so a project starting on 15 Aug
 * is scored over 15 Aug – 14 Sep, then 15 Sep – 14 Oct, never over calendar
 * months and never over the whole target divided by twelve.
 */
class ProjectMilestoneProgressTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    private Team $team;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Acme', 'code' => 'ACME', 'is_active' => true]);
        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Sales',
            'code' => 'SAL',
            'is_active' => true,
        ]);
        $this->team = Team::create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'name' => 'Alpha',
            'is_active' => true,
        ]);
        $this->client = Client::create([
            'name' => 'Globex',
            'code' => 'GLX',
            'email' => 'globex@example.com',
            'phone' => '01711111111',
            'is_active' => true,
            'password' => 'secret123',
        ]);
    }

    /**
     * Start 15 Aug 2026, 3-month challenge, 300,000 target — so three
     * milestones of 100,000: 15 Aug–14 Sep, 15 Sep–14 Oct, 15 Oct–14 Nov.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function makeProject(string $name = 'Challenge One', array $overrides = []): Project
    {
        return Project::create(array_merge([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'client_id' => $this->client->id,
            'team_id' => $this->team->id,
            'business_status' => BusinessStatus::CampaignRunning,
            'project_name' => $name,
            'business_name' => $name,
            'start_date' => '2026-08-15',
            'contract_months' => 12,
            'end_date' => '2027-08-15',
            'package_amount' => 50000,
            'amount_paid' => 0,
            'project_type' => 'challenge_based',
            'sales_target' => 300000,
            'target_start_date' => '2026-08-15',
            'target_months' => 3,
            'target_deadline' => '2026-11-15',
        ], $overrides));
    }

    private function report(Project $project, string $weekStart, string $weekEnd, float $total): SalesReport
    {
        return SalesReport::create([
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'total_sales' => $total,
        ]);
    }

    private function progress(Project $project): MilestoneProgress
    {
        return app(MilestoneProgressService::class)->forProject($project);
    }

    public function test_milestones_step_one_month_at_a_time_from_the_start_date(): void
    {
        $project = $this->makeProject();

        $periods = $project->milestones()->get()
            ->map(fn ($m) => [
                $m->period_start->toDateString(),
                $m->period_end->toDateString(),
                (float) $m->target_amount,
            ])->all();

        $this->assertSame([
            ['2026-08-15', '2026-09-14', 100000.0],
            ['2026-09-15', '2026-10-14', 100000.0],
            ['2026-10-15', '2026-11-14', 100000.0],
        ], $periods);
    }

    public function test_a_project_that_has_not_started_yet_sits_at_zero(): void
    {
        $project = $this->makeProject();

        $this->travelTo('2026-08-01');
        $progress = $this->progress($project);

        $this->assertFalse($progress->started);
        $this->assertSame(0.0, $progress->progress);
        $this->assertSame(0.0, $progress->achieved);
        $this->assertSame('2026-08-15', $progress->periodStart);
        $this->assertSame(HealthStatus::Upcoming, $progress->health);
    }

    public function test_the_first_month_is_scored_against_the_monthly_target(): void
    {
        $project = $this->makeProject();
        $this->report($project, '2026-08-17', '2026-08-23', 72000);

        $this->travelTo('2026-08-20');
        $progress = $this->progress($project);

        $this->assertSame(1, $progress->sequence);
        $this->assertSame(100000.0, $progress->target);
        $this->assertSame(72000.0, $progress->achieved);
        $this->assertSame(72.0, $progress->progress);
        $this->assertSame(HealthStatus::AtRisk, $progress->health);
    }

    public function test_an_earlier_months_sales_do_not_leak_into_the_current_one(): void
    {
        $project = $this->makeProject();
        $this->report($project, '2026-08-17', '2026-08-23', 100000);
        $this->report($project, '2026-09-21', '2026-09-27', 40000);

        $this->travelTo('2026-09-25');
        $progress = $this->progress($project);

        $this->assertSame(2, $progress->sequence);
        $this->assertSame('2026-09-15', $progress->periodStart);
        $this->assertSame('2026-10-14', $progress->periodEnd);
        $this->assertSame(40000.0, $progress->achieved);
        $this->assertSame(40.0, $progress->progress);
        $this->assertSame(HealthStatus::OffTrack, $progress->health);
    }

    public function test_a_month_with_no_sales_of_its_own_reads_zero(): void
    {
        $project = $this->makeProject();
        $this->report($project, '2026-08-17', '2026-08-23', 100000);

        $this->travelTo('2026-10-20');
        $progress = $this->progress($project);

        $this->assertSame(3, $progress->sequence);
        $this->assertSame(0.0, $progress->achieved);
        $this->assertSame(0.0, $progress->progress);
        $this->assertSame(HealthStatus::OffTrack, $progress->health);
    }

    public function test_sales_exactly_on_target_read_one_hundred_percent(): void
    {
        $project = $this->makeProject();
        $this->report($project, '2026-08-17', '2026-08-23', 100000);

        $this->travelTo('2026-08-25');
        $progress = $this->progress($project);

        $this->assertSame(100.0, $progress->progress);
        $this->assertSame(100.0, $progress->rawProgress);
        $this->assertSame(HealthStatus::OnTrack, $progress->health);
    }

    public function test_sales_above_target_cap_the_bar_but_keep_the_amount(): void
    {
        $project = $this->makeProject();
        $this->report($project, '2026-08-17', '2026-08-23', 150000);

        $this->travelTo('2026-08-25');
        $progress = $this->progress($project);

        $this->assertSame(100.0, $progress->progress);
        $this->assertSame(150.0, $progress->rawProgress);
        $this->assertSame(150000.0, $progress->achieved);
        $this->assertSame(HealthStatus::OnTrack, $progress->health);
    }

    public function test_past_the_deadline_the_final_milestone_stays_current(): void
    {
        $project = $this->makeProject();
        $this->report($project, '2026-10-19', '2026-10-25', 90000);
        $this->report($project, '2026-11-16', '2026-11-22', 500000);

        $this->travelTo('2026-12-10');
        $progress = $this->progress($project);

        $this->assertSame(3, $progress->sequence);
        $this->assertSame('2026-10-15', $progress->periodStart);
        $this->assertSame('2026-11-14', $progress->periodEnd);
        $this->assertSame(90000.0, $progress->achieved);
        $this->assertSame(90.0, $progress->progress);
        $this->assertSame(HealthStatus::OnTrack, $progress->health);
    }

    /**
     * Reports are weekly and milestones start mid-month, so a week can straddle
     * a boundary. It belongs to the milestone its week_start falls in — counted
     * once, never dropped and never counted twice.
     */
    public function test_a_straddling_week_belongs_to_the_milestone_its_week_start_is_in(): void
    {
        $project = $this->makeProject();
        $this->report($project, '2026-09-11', '2026-09-17', 30000);

        $this->travelTo('2026-09-13');
        $this->assertSame(30000.0, $this->progress($project)->achieved);

        $this->travelTo('2026-09-20');
        $this->assertSame(0.0, $this->progress($project)->achieved);
    }

    public function test_the_stored_health_status_follows_the_current_milestone(): void
    {
        $this->travelTo('2026-08-25');

        $project = $this->makeProject();
        $this->report($project, '2026-08-17', '2026-08-23', 95000);

        $this->assertSame(HealthStatus::OnTrack, $project->refresh()->health_status);
        $this->assertSame(
            95000.0,
            (float) $project->milestones()->where('sequence', 1)->value('achieved_amount'),
        );
    }

    public function test_the_list_endpoint_serves_the_resolved_milestone(): void
    {
        $project = $this->makeProject();
        $this->report($project, '2026-08-17', '2026-08-23', 72000);

        $this->travelTo('2026-08-20');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/projects')
            ->assertOk()
            ->assertJsonPath('result.data.0.milestone.period_start', '2026-08-15')
            ->assertJsonPath('result.data.0.milestone.period_end', '2026-09-14')
            ->assertJsonPath('result.data.0.milestone.target', 100000)
            ->assertJsonPath('result.data.0.milestone.achieved', 72000)
            ->assertJsonPath('result.data.0.milestone.progress', 72)
            ->assertJsonPath('result.data.0.achieved_sales', 72000)
            ->assertJsonPath('result.data.0.health_status', HealthStatus::AtRisk->value);
    }

    public function test_the_project_page_serves_the_resolved_milestone(): void
    {
        $project = $this->makeProject();
        $this->report($project, '2026-08-17', '2026-08-23', 72000);

        $this->travelTo('2026-08-20');

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('result.milestone.progress', 72)
            ->assertJsonPath('result.milestone.label', 'Month 1')
            ->assertJsonPath('result.sales_target', '300000.00')
            ->assertJsonPath('result.achieved_sales', 72000);
    }

    /** The graph data must not cost one query per row. */
    public function test_the_list_endpoint_does_not_query_per_project(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 3; $i++) {
            $project = $this->makeProject("Small {$i}");
            $this->report($project, '2026-08-17', '2026-08-23', 1000 * $i);
        }

        $this->travelTo('2026-08-20');

        $this->actingAs($user)->getJson('/v1/admin/projects?per_page=100')->assertOk();

        DB::enableQueryLog();
        $this->actingAs($user)->getJson('/v1/admin/projects?per_page=100')->assertOk();
        $withThree = count(DB::getQueryLog());
        DB::flushQueryLog();

        for ($i = 4; $i <= 15; $i++) {
            $project = $this->makeProject("Small {$i}");
            $this->report($project, '2026-08-17', '2026-08-23', 1000 * $i);
        }

        DB::flushQueryLog();
        $this->actingAs($user)
            ->getJson('/v1/admin/projects?per_page=100')
            ->assertOk()
            ->assertJsonCount(15, 'result.data');
        $withFifteen = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(
            $withThree,
            $withFifteen,
            'The project list ran more queries for 15 projects than for 3 — that is an N+1.',
        );
    }
}
