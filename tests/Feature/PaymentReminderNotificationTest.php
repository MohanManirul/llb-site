<?php

namespace Tests\Feature;

use App\Enums\BusinessStatus;
use App\Enums\PaymentNotificationType;
use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Notifications\PaymentDueReminder;
use App\Notifications\PaymentOverdueReminder;
use App\Notifications\PaymentReceived;
use App\Services\Payment\NotificationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Payment notifications live in the framework's `notifications` table. Every
 * reminder carries a `dedupe_key` under a unique index, so a repeated command
 * run cannot notify the same client twice for the same due date.
 */
class PaymentReminderNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private Project $project;

    private User $user;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->client = Client::create([
            'name' => 'Globex',
            'code' => 'GLX',
            'email' => 'globex@example.com',
            'phone' => '01711111111',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        $this->user = User::factory()->create();

        $this->project = $this->makeProject(today()->addDays(3)->toDateString());

        $this->service = app(NotificationService::class);
    }

    private function makeProject(?string $nextPaymentDate, string $name = 'Acme Website'): Project
    {
        return Project::create([
            'company_id' => Company::first()->id,
            'department_id' => Department::first()->id,
            'team_id' => Team::first()->id,
            'client_id' => $this->client->id,
            'business_status' => BusinessStatus::CampaignRunning,
            'project_name' => $name,
            'business_name' => $name,
            'contact_person' => 'A',
            'contact_email' => 'a@example.com',
            'contact_phone' => '01700000000',
            'project_type' => 'regular',
            'package_amount' => 10000,
            'total_amount' => 10000,
            'amount_paid' => 0,
            'contract_months' => 12,
            'start_date' => '2026-01-01',
            'end_date' => '2027-01-01',
            'next_payment_date' => $nextPaymentDate,
            'health_status' => 'upcoming',
        ]);
    }

    public function test_it_records_a_due_reminder_with_the_payload_the_frontend_needs(): void
    {
        $this->assertTrue($this->service->dueReminder($this->project, 2500.0));

        $notification = $this->client->notifications()->sole();

        $this->assertSame(PaymentDueReminder::class, $notification->type);
        $this->assertNull($notification->read_at);

        $this->assertSame(
            PaymentNotificationType::PaymentDueReminder->value,
            $notification->data['type'],
        );
        $this->assertSame($this->project->id, $notification->data['project_id']);
        $this->assertSame($this->client->id, $notification->data['client_id']);
        $this->assertEquals(2500.0, $notification->data['amount']);
        $this->assertSame(
            $this->project->next_payment_date->toDateString(),
            $notification->data['due_date'],
        );
        $this->assertSame("/projects/{$this->project->id}", $notification->data['link']);
    }

    public function test_it_records_an_overdue_reminder(): void
    {
        $overdue = $this->makeProject(today()->subDays(10)->toDateString(), 'Overdue Site');

        $this->assertTrue($this->service->overdueReminder($overdue, 4000.0));

        $notification = $this->client->notifications()->latest()->first();

        $this->assertSame(
            PaymentNotificationType::PaymentOverdueReminder->value,
            $notification->data['type'],
        );
        $this->assertSame(10, $notification->data['days_overdue']);
    }

    public function test_an_overdue_project_is_chased_once_a_day_until_it_is_paid(): void
    {
        $overdue = $this->makeProject(today()->subDays(10)->toDateString(), 'Overdue Site');

        $this->assertTrue($this->service->overdueReminder($overdue, 4000.0));
        $this->assertFalse($this->service->overdueReminder($overdue, 4000.0));

        $this->travel(1)->days();

        $this->assertTrue($this->service->overdueReminder($overdue->fresh(), 4000.0));

        $this->assertSame(
            2,
            $this->client->notifications()
                ->where('type', PaymentOverdueReminder::class)
                ->count(),
        );
    }

    public function test_the_overdue_dedupe_key_carries_todays_date(): void
    {
        $overdue = $this->makeProject(today()->subDays(10)->toDateString(), 'Overdue Site');

        $this->service->overdueReminder($overdue, 4000.0);

        $this->assertSame(
            "payment_overdue_reminder:project:{$overdue->id}:".
            $overdue->next_payment_date->toDateString().':'.today()->toDateString(),
            DatabaseNotification::sole()->dedupe_key,
        );
    }

    public function test_it_skips_a_project_with_no_next_payment_date(): void
    {
        $undated = $this->makeProject(null, 'Undated Site');

        $this->assertFalse($this->service->dueReminder($undated, 1000.0));
        $this->assertFalse($this->service->overdueReminder($undated, 1000.0));

        $this->assertSame(0, $this->client->notifications()->count());
    }

    public function test_running_the_reminder_command_twice_notifies_only_once(): void
    {
        $this->artisan('payment:create-reminders')->assertSuccessful();
        $this->artisan('payment:create-reminders')->assertSuccessful();

        $this->assertSame(1, $this->client->notifications()->count());
    }

    public function test_the_dedupe_key_is_unique_at_the_database_level(): void
    {
        $this->service->dueReminder($this->project, 2500.0);

        $key = DatabaseNotification::sole()->dedupe_key;

        $this->assertSame(
            "payment_due_reminder:project:{$this->project->id}:".
            $this->project->next_payment_date->toDateString(),
            $key,
        );

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => PaymentDueReminder::class,
            'dedupe_key' => $key,
            'notifiable_type' => $this->client->getMorphClass(),
            'notifiable_id' => $this->client->id,
            'data' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_notifications_without_a_dedupe_key_are_never_blocked(): void
    {
        foreach (range(1, 3) as $index) {
            $this->client->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'client-import',
                'data' => ['type' => 'client_import', 'message' => "import {$index}"],
            ]);
        }

        $this->assertSame(3, $this->client->notifications()->count());
        $this->assertSame(3, DatabaseNotification::whereNull('dedupe_key')->count());
    }

    public function test_a_payment_notification_carries_its_invoice(): void
    {
        $payment = Payment::create([
            'project_id' => $this->project->id,
            'payment_type' => 'bank_transfer',
            'amount' => 2500,
            'payment_date' => today()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $invoice = Invoice::create([
            'project_id' => $this->project->id,
            'invoice_number' => 'INV-2026-08-0001',
            'invoice_date' => today()->toDateString(),
            'total_amount' => 10000,
            'paid_amount' => 2500,
            'due_amount' => 7500,
            'due_date' => today()->addDays(30)->toDateString(),
        ]);

        $this->assertTrue($this->service->paymentReceived($payment, $invoice));

        $notification = $this->client->notifications()->sole();

        $this->assertSame(PaymentReceived::class, $notification->type);
        $this->assertSame(
            PaymentNotificationType::PaymentReceivedConfirmation->value,
            $notification->data['type'],
        );
        $this->assertSame($payment->id, $notification->data['payment_id']);
        $this->assertSame($invoice->id, $notification->data['invoice_id']);
        $this->assertSame('INV-2026-08-0001', $notification->data['invoice_number']);

        $this->assertFalse($this->service->paymentReceived($payment, $invoice));
        $this->assertSame(1, $this->client->notifications()->count());
    }
}
