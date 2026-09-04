<?php

namespace Tests\Feature;

use App\Enums\BusinessStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\SalesReport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Covers what Dashboard.jsx relies on now that the page carries no server
 * props: /v1/admin/dashboard/report has to hand back the heading, stat cards and
 * recent-projects table the old Inertia controller used to render. If this
 * endpoint goes quiet the page shows an empty dashboard with no error.
 */
class ApiDashboardReportTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    private Team $team;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Acme Corp', 'code' => 'ACME', 'is_active' => true]);
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
        $this->client = Client::create([
            'name' => 'Globex Ltd',
            'code' => 'GLX',
            'email' => 'glx@example.com',
            'phone' => '01700000001',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        Employee::create([
            'user_id' => User::factory()->create([
                'name' => 'Rakib Hasan',
                'email' => 'rakib@example.com',
            ])->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => Designation::create([
                'name' => 'Marketer',
                'is_active' => true,
            ])->id,
            'is_active' => true,
        ]);
    }

    private function makeProject(string $businessName, ?Client $client = null): Project
    {
        return Project::create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'client_id' => ($client ?? $this->client)->id,
            'team_id' => $this->team->id,
            'business_status' => BusinessStatus::CampaignRunning,
            'project_name' => $businessName,
            'business_name' => $businessName,
            'start_date' => '2026-01-01',
            'contract_months' => 12,
            'end_date' => '2027-01-01',
            'package_amount' => 1000,
            'amount_paid' => 250,
            'project_type' => 'regular',
            'health_status' => 'upcoming',
        ]);
    }

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/v1/admin/dashboard/report')->assertUnauthorized();
    }

    public function test_the_dashboard_page_is_a_shell_with_no_report_prop(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/dashboard/page')
                ->missing('report'));
    }

    public function test_it_returns_the_admin_report_the_page_renders(): void
    {
        $this->makeProject('Acme Website');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'result' => [
                    'heading',
                    'cards' => [['label', 'value', 'icon', 'color']],
                    'recent' => [
                        'title',
                        'columns' => [['key', 'header']],
                        'rows' => [['id', 'href', 'business', 'client', 'package', 'paid', 'date']],
                    ],
                ],
            ]);
    }

    public function test_the_stat_cards_carry_the_live_counts(): void
    {
        $this->makeProject('Acme Website');
        $this->makeProject('Globex Portal');

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk();

        $cards = collect($response->json('result.cards'))->pluck('value', 'label');

        $this->assertSame('1', $cards['Clients']);
        $this->assertSame('2', $cards['Projects']);
        $this->assertSame('1', $cards['Employees']);
        $this->assertSame('1', $cards['Teams']);
    }

    /** Row click on the recent table opens the project, so href must be there. */
    public function test_a_recent_row_links_to_its_project(): void
    {
        $project = $this->makeProject('Acme Website');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk()
            ->assertJsonPath('result.recent.rows.0.href', '/admin/projects/'.$project->id)
            ->assertJsonPath('result.recent.rows.0.client', 'Globex Ltd');
    }

    /**
     * A soft-deleted client takes its projects off every dashboard — the counts
     * and the recent table both drop them, so no row is left pointing at a
     * client that is no longer there.
     */
    public function test_a_soft_deleted_clients_projects_leave_the_report(): void
    {
        $this->makeProject('Acme Website');

        $gone = Client::create([
            'name' => 'Initech',
            'email' => 'initech@example.com',
            'phone' => '01700000002',
            'is_active' => true,
            'password' => 'secret123',
        ]);
        $this->makeProject('Initech Portal', $gone);

        $gone->delete();

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk();

        $cards = collect($response->json('result.cards'))->pluck('value', 'label');

        $this->assertSame('1', $cards['Projects']);
        $this->assertSame('1', $cards['Clients']);

        $this->assertSame(
            ['Acme Website'],
            collect($response->json('result.recent.rows'))->pluck('business')->all(),
        );
    }

    /** Same rule on the raw /v1/admin/dashboard endpoint the mobile app hits. */
    public function test_a_soft_deleted_clients_projects_leave_the_overview(): void
    {
        $this->makeProject('Acme Website');

        $gone = Client::create([
            'name' => 'Initech',
            'email' => 'initech@example.com',
            'phone' => '01700000002',
            'is_active' => true,
            'password' => 'secret123',
        ]);
        $this->makeProject('Initech Portal', $gone);

        $gone->delete();

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('super-admin', 'web'));

        $response = $this->actingAs($admin)
            ->getJson('/v1/admin/dashboard')
            ->assertOk();

        $this->assertSame(1, $response->json('result.sections.overview.total_projects'));
        $this->assertSame(1, $response->json('result.sections.overview.active_projects'));
        $this->assertSame(
            ['Acme Website'],
            collect($response->json('result.sections.overview.recent_projects'))->pluck('business_name')->all(),
        );
        $this->assertSame(1000.0, (float) $response->json('result.sections.finance.total_package_amount'));
    }

    /**
     * The company-wide numbers hang off `view dashboard` alone — holding a
     * neighbouring permission like `view projects` is not enough.
     */
    public function test_the_overview_needs_the_dashboard_permission(): void
    {
        $this->makeProject('Acme Website');

        $role = Role::findOrCreate('reader', 'web');
        $role->syncPermissions([Permission::findOrCreate('view projects', 'web')]);

        $reader = User::factory()->create();
        $reader->assignRole($role);

        $report = $this->actingAs($reader)
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk();

        $cards = collect($report->json('result.cards'))->pluck('value', 'label');
        $this->assertArrayNotHasKey('Clients', $cards->all());
        $this->assertNull($report->json('result.recent'));

        $overview = $this->actingAs($reader)
            ->getJson('/v1/admin/dashboard')
            ->assertOk();

        $this->assertNull($overview->json('result.sections.overview'));
    }

    public function test_the_dashboard_permission_alone_unlocks_the_overview(): void
    {
        $this->makeProject('Acme Website');

        $role = Role::findOrCreate('viewer', 'web');
        $role->syncPermissions([Permission::findOrCreate('view dashboard', 'web')]);

        $viewer = User::factory()->create();
        $viewer->assignRole($role);

        $this->actingAs($viewer)
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk()
            ->assertJsonPath('result.heading', 'Dashboard Overview');

        $this->assertSame(
            1,
            $this->actingAs($viewer)
                ->getJson('/v1/admin/dashboard')
                ->assertOk()
                ->json('result.sections.overview.total_projects'),
        );
    }

    public function test_an_employee_without_any_role_gets_their_own_report(): void
    {
        $this->makeProject('Acme Website');

        $worker = User::factory()->create(['name' => 'Role Less']);
        Employee::create([
            'user_id' => $worker->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => Designation::firstOrCreate(['name' => 'Marketer'])->id,
            'is_active' => true,
        ]);

        $response = $this->be($worker)
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk();

        $this->assertSame('My work — Role Less', $response->json('result.heading'));

        $cards = collect($response->json('result.cards'))->pluck('value', 'label');
        $this->assertArrayNotHasKey('Clients', $cards->all());
        $this->assertSame('0', $cards['Assigned Projects']);
    }

    private function makeWeek(Project $project, string $weekStart, float $sales, float $spend, int $orders): SalesReport
    {
        return SalesReport::create([
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'week_start' => $weekStart,
            'week_end' => Carbon::parse($weekStart)->addDays(6)->toDateString(),
            'total_sales' => $sales,
            'total_amount_spent' => $spend,
            'total_order_quantity' => $orders,
        ]);
    }

    public function test_the_trend_spans_exactly_the_filtered_range(): void
    {
        $this->makeWeek($this->makeProject('Acme Website'), '2026-07-13', 7000, 700, 70);

        $trend = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/dashboard/report?date_from=2026-07-15&date_to=2026-08-13')
            ->assertOk()
            ->json('result.trend');

        $this->assertCount(30, $trend);
        $this->assertSame('2026-07-15', $trend[0]['date']);
        $this->assertSame('2026-08-13', $trend[29]['date']);
    }

    public function test_a_week_overlapping_the_range_contributes_only_its_days_inside_it(): void
    {
        $project = $this->makeProject('Acme Website');

        $this->makeWeek($project, '2026-07-13', 7000, 700, 70);
        $this->makeWeek($project, '2026-07-20', 7000, 700, 70);

        $trend = collect(
            $this->actingAs(User::factory()->create())
                ->getJson('/v1/admin/dashboard/report?date_from=2026-07-15&date_to=2026-07-26')
                ->assertOk()
                ->json('result.trend')
        )->keyBy('date');

        $this->assertSame(1000.0, round((float) $trend['2026-07-15']['sales'], 2));
        $this->assertSame(1000.0, round((float) $trend['2026-07-26']['sales'], 2));
        $this->assertSame(12000.0, round($trend->sum(fn (array $day) => (float) $day['sales']), 2));
    }

    public function test_a_range_with_no_reports_still_plots_its_days(): void
    {
        $this->makeProject('Acme Website');

        $trend = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/dashboard/report?date_from=2026-07-15&date_to=2026-07-21')
            ->assertOk()
            ->json('result.trend');

        $this->assertCount(7, $trend);
        $this->assertSame(0.0, (float) collect($trend)->sum('sales'));
    }

    public function test_the_finance_total_sales_matches_the_plotted_days(): void
    {
        $this->makeWeek($this->makeProject('Acme Website'), '2026-07-13', 7000, 700, 70);

        $role = Role::findOrCreate('finance-viewer', 'web');
        $role->syncPermissions([
            Permission::findOrCreate('view dashboard', 'web'),
            Permission::findOrCreate('view finance', 'web'),
        ]);

        $viewer = User::factory()->create();
        $viewer->assignRole($role);

        $report = $this->actingAs($viewer)
            ->getJson('/v1/admin/dashboard/report?date_from=2026-07-15&date_to=2026-08-13')
            ->assertOk();

        $plotted = collect($report->json('result.trend'))->sum('sales');
        $card = collect($report->json('result.finance'))->firstWhere('label', 'Total Sales');

        $this->assertSame(round($plotted, 2), round((float) $card['raw'], 2));
        $this->assertSame(5000.0, round((float) $card['raw'], 2));
    }

    public function test_a_user_with_neither_permissions_nor_employment_gets_nothing(): void
    {
        $this->makeProject('Acme Website');

        $outsider = User::factory()->create();
        $outsider->assignRole(Role::findOrCreate('nobody', 'web'));

        $this->actingAs($outsider)
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk()
            ->assertJsonPath('result.heading', 'My work')
            ->assertJsonPath('result.cards', []);
    }
}
