<?php

namespace Tests\Feature;

use App\Enums\BusinessStatus;
use App\Jobs\ImportSalesReportsJob;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApiProjectSalesReportCsvImportTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ([
            'view projects',
            'view sales reports', 'create sales reports',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('staff', 'web');

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

        $this->project = Project::create([
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

    /**
     * The person the import is for: the employee the project is assigned to,
     * holding the scoped report permissions and nothing wider.
     */
    private function submitter(): User
    {
        $role = Role::findByName('staff', 'web');
        $role->syncPermissions(['view projects', 'view sales reports', 'create sales reports']);

        $user = User::factory()->create();
        $user->syncRoles(['staff']);

        $this->project->update([
            'assigned_employee_id' => $this->makeEmployee($user)->id,
        ]);

        return $user;
    }

    private function makeEmployee(User $user): Employee
    {
        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $this->project->company_id,
            'department_id' => $this->project->department_id,
            'designation_id' => Designation::firstOrCreate(
                ['name' => 'Marketer'],
                ['is_active' => true],
            )->id,
            'is_active' => true,
        ]);
    }

    private function upload(string $body, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->submitter())
            ->postJson("/v1/admin/projects/{$this->project->id}/sales-reports/import", [
                'file' => UploadedFile::fake()->createWithContent('reports.csv', $body),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function statusOf(string $importId, ?User $user = null): array
    {
        return $this->actingAs($user ?? $this->submitter())
            ->getJson("/v1/admin/projects/{$this->project->id}/sales-reports/import/{$importId}")
            ->assertOk()
            ->json('result');
    }

    public function test_the_sample_file_the_modal_offers_actually_imports(): void
    {
        $path = public_path('samples/sales-reports-import-sample.csv');

        $this->assertFileExists($path, 'The download link in ReportCsvUploadModal points at this file.');

        $response = $this->actingAs($this->submitter())
            ->postJson("/v1/admin/projects/{$this->project->id}/sales-reports/import", [
                'file' => new UploadedFile($path, 'sales-reports-import-sample.csv', 'text/csv', null, true),
            ])
            ->assertOk()
            ->assertJsonPath('result.rows', 4);

        $this->assertSame(4, SalesReport::count());

        $status = $this->statusOf($response->json('result.import_id'));

        $this->assertSame('finished', $status['status']);
        $this->assertSame(4, $status['imported']);
        $this->assertSame(0, $status['skipped']);
    }

    public function test_it_derives_the_week_end_from_the_week_start(): void
    {
        $this->upload(
            "Week Start,Total Sales,Total Order Quantity,Total Amount Spent,Description\n".
            "2026-01-05,1000,10,200,First week\n"
        )->assertOk();

        $report = SalesReport::sole();

        $this->assertSame('2026-01-05', $report->week_start->toDateString());
        $this->assertSame('2026-01-11', $report->week_end->toDateString());
        $this->assertSame($this->project->company_id, $report->company_id);
        $this->assertSame('First week', $report->description);
    }

    public function test_a_row_whose_week_overlaps_an_existing_report_is_skipped(): void
    {
        SalesReport::create([
            'company_id' => $this->project->company_id,
            'project_id' => $this->project->id,
            'week_start' => '2026-01-05',
            'week_end' => '2026-01-11',
            'total_sales' => 500,
            'total_order_quantity' => 5,
            'total_amount_spent' => 100,
        ]);

        $response = $this->upload(
            "Week Start,Total Sales,Total Order Quantity,Total Amount Spent\n".
            "2026-01-07,1000,10,200\n".
            "2026-01-12,1200,12,300\n"
        )->assertOk();

        $status = $this->statusOf($response->json('result.import_id'));

        $this->assertSame(1, $status['imported']);
        $this->assertSame(1, $status['skipped']);
        $this->assertStringContainsString('overlap', $status['details'][0]['reason']);
        $this->assertSame(2, SalesReport::count());
    }

    public function test_two_overlapping_weeks_inside_one_file_keep_only_the_first(): void
    {
        $response = $this->upload(
            "Week Start,Total Sales,Total Order Quantity,Total Amount Spent\n".
            "2026-01-05,1000,10,200\n".
            "2026-01-08,1100,11,210\n"
        )->assertOk();

        $status = $this->statusOf($response->json('result.import_id'));

        $this->assertSame(1, $status['imported']);
        $this->assertSame(1, $status['skipped']);
        $this->assertSame('2026-01-05', SalesReport::sole()->week_start->toDateString());
    }

    public function test_a_week_end_column_that_is_not_seven_days_is_refused(): void
    {
        $response = $this->upload(
            "Week Start,Week End,Total Sales,Total Order Quantity,Total Amount Spent\n".
            "2026-01-05,2026-01-20,1000,10,200\n"
        )->assertOk();

        $status = $this->statusOf($response->json('result.import_id'));

        $this->assertSame(0, $status['imported']);
        $this->assertSame(1, $status['skipped']);
        $this->assertStringContainsString('2026-01-11', $status['details'][0]['reason']);
        $this->assertSame(0, SalesReport::count());
    }

    public function test_a_matching_week_end_column_is_accepted(): void
    {
        $this->upload(
            "Week Start,Week End,Total Sales,Total Order Quantity,Total Amount Spent\n".
            "2026-01-05,2026-01-11,1000,10,200\n"
        )->assertOk();

        $this->assertSame(1, SalesReport::count());
    }

    public function test_rows_that_break_the_form_rules_are_skipped(): void
    {
        $response = $this->upload(
            "Week Start,Total Sales,Total Order Quantity,Total Amount Spent\n".
            "not-a-date,1000,10,200\n".
            "2026-01-05,-5,10,200\n".
            "2026-01-12,1000,2.5,200\n".
            "2026-01-19,1000,10,\n"
        )->assertOk();

        $status = $this->statusOf($response->json('result.import_id'));

        $this->assertSame(0, $status['imported']);
        $this->assertSame(4, $status['skipped']);
        $this->assertSame(0, SalesReport::count());
    }

    public function test_it_reads_aliased_headers_a_byte_order_mark_and_thousand_separators(): void
    {
        $this->upload(
            "\u{FEFF}Start Date,Sales,Orders,Amount Spent,Notes\n".
            "05/01/2026,\"1,250.50\",10,\"2,000\",Weekly note\n"
        )->assertOk();

        $report = SalesReport::sole();

        $this->assertSame('2026-01-05', $report->week_start->toDateString());
        $this->assertSame('1250.50', $report->total_sales);
        $this->assertSame('2000.00', $report->total_amount_spent);
    }

    public function test_the_upload_is_handed_to_a_queued_job(): void
    {
        Queue::fake();

        $this->upload(
            "Week Start,Total Sales,Total Order Quantity,Total Amount Spent\n".
            "2026-01-05,1000,10,200\n"
        )->assertOk();

        Queue::assertPushed(ImportSalesReportsJob::class, 1);
        $this->assertSame(0, SalesReport::count());
    }

    public function test_a_queued_import_reports_its_progress_until_the_job_runs(): void
    {
        Queue::fake();

        $response = $this->upload(
            "Week Start,Total Sales,Total Order Quantity,Total Amount Spent\n".
            "2026-01-05,1000,10,200\n"
        )->assertOk();

        $status = $this->statusOf($response->json('result.import_id'));

        $this->assertSame('queued', $status['status']);
        $this->assertSame(1, $status['total']);
    }

    public function test_a_bad_header_is_rejected_before_anything_is_queued(): void
    {
        Queue::fake();

        $this->upload("Week,Revenue\n2026-01-05,1000\n")
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.file.0',
                'CSV is missing required columns: total_sales, total_order_quantity, total_amount_spent.'
            );

        Queue::assertNothingPushed();
    }

    public function test_an_empty_csv_is_rejected(): void
    {
        $this->upload('')
            ->assertStatus(422)
            ->assertJsonPath('errors.file.0', 'The CSV file is empty.');
    }

    public function test_a_csv_with_only_a_header_is_rejected(): void
    {
        $this->upload("Week Start,Total Sales,Total Order Quantity,Total Amount Spent\n")
            ->assertStatus(422)
            ->assertJsonPath('errors.file.0', 'The CSV file has no data rows.');
    }

    public function test_it_validates_the_uploaded_file_type(): void
    {
        $this->actingAs($this->submitter())
            ->postJson("/v1/admin/projects/{$this->project->id}/sales-reports/import", [
                'file' => UploadedFile::fake()->create('reports.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_a_viewer_without_the_submit_right_cannot_import(): void
    {
        $role = Role::findOrCreate('reader', 'web');
        $role->syncPermissions(['view projects', 'view sales reports']);

        $viewer = User::factory()->create();
        $viewer->syncRoles(['reader']);

        $this->upload(
            "Week Start,Total Sales,Total Order Quantity,Total Amount Spent\n".
            "2026-01-05,1000,10,200\n",
            $viewer,
        )->assertForbidden();

        $this->assertSame(0, SalesReport::count());
    }

    public function test_an_import_of_another_project_is_not_readable_here(): void
    {
        $submitter = $this->submitter();

        $response = $this->upload(
            "Week Start,Total Sales,Total Order Quantity,Total Amount Spent\n".
            "2026-01-05,1000,10,200\n",
            $submitter,
        )->assertOk();

        // Assigned to the same employee, so the refusal below is the import id
        // failing to match this project — not the project being out of reach.
        $other = Project::create([
            ...$this->project->only([
                'company_id', 'department_id', 'team_id', 'client_id', 'business_status',
                'assigned_employee_id', 'contact_person', 'contact_email',
                'contact_phone', 'project_type', 'package_amount', 'amount_paid',
                'contract_months', 'start_date', 'end_date', 'health_status',
            ]),
            'project_name' => 'Second',
            'business_name' => 'Second',
        ]);

        $importId = $response->json('result.import_id');

        $this->actingAs($submitter)
            ->getJson("/v1/admin/projects/{$other->id}/sales-reports/import/{$importId}")
            ->assertNotFound();
    }

    public function test_it_rejects_unauthenticated_uploads(): void
    {
        $this->postJson("/v1/admin/projects/{$this->project->id}/sales-reports/import")
            ->assertUnauthorized();
    }
}
