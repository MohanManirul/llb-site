<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiActivityLogIndexTest extends TestCase
{
    use RefreshDatabase;

    private function makeLog(string $description, string $type = 'admin', ?string $createdAt = null): ActivityLog
    {
        $log = activity()->type($type)->log($description);

        if ($createdAt !== null) {
            $log->forceFill(['created_at' => $createdAt])->save();
        }

        return $log;
    }

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/v1/admin/activity-logs')->assertUnauthorized();
    }

    public function test_it_returns_a_paginator_the_datatable_can_render(): void
    {
        $this->makeLog('Business status created');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/activity-logs')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'result' => [
                    'data' => [['id', 'type', 'description', 'subject_type', 'subject_id', 'causer', 'created_at']],
                    'links' => ['prev', 'next'],
                    'meta' => ['current_page', 'from', 'to', 'per_page'],
                ],
            ]);
    }

    public function test_it_searches_by_description(): void
    {
        $this->makeLog('User created');
        $this->makeLog('Role deleted');

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/activity-logs?search=User')
            ->assertOk();

        $response->assertJsonCount(1, 'result.data');
        $this->assertSame('User created', $response->json('result.data.0.description'));
    }

    public function test_it_filters_by_type(): void
    {
        $this->makeLog('One', 'admin');
        $this->makeLog('Two', 'auth');

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/activity-logs?type=auth')
            ->assertOk();

        $response->assertJsonCount(1, 'result.data');
        $this->assertSame('Two', $response->json('result.data.0.description'));
    }

    public function test_it_filters_by_subject_type(): void
    {
        $subject = User::factory()->create();
        activity()->performedOn($subject)->log('With subject');
        $this->makeLog('Without subject');

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/activity-logs?subject_type='.urlencode(User::class))
            ->assertOk();

        $response->assertJsonCount(1, 'result.data');
        $this->assertSame('User', $response->json('result.data.0.subject_type'));
    }

    public function test_it_filters_by_date_range_inclusively(): void
    {
        $this->makeLog('Old', 'admin', '2026-01-01 10:00:00');
        $this->makeLog('Edge', 'admin', '2026-02-10 23:30:00');
        $this->makeLog('New', 'admin', '2026-03-01 10:00:00');

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/activity-logs?date_from=2026-02-01&date_to=2026-02-10')
            ->assertOk();

        // date_to must cover the whole day, not stop at midnight.
        $response->assertJsonCount(1, 'result.data');
        $this->assertSame('Edge', $response->json('result.data.0.description'));
    }

    public function test_it_sorts_by_created_at_descending_by_default(): void
    {
        $this->makeLog('Older', 'admin', '2026-01-01 10:00:00');
        $this->makeLog('Newer', 'admin', '2026-03-01 10:00:00');

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/activity-logs')
            ->assertOk();

        $this->assertSame('Newer', $response->json('result.data.0.description'));
    }

    public function test_it_resolves_the_causer_name(): void
    {
        $user = User::factory()->create(['name' => 'Rakib']);

        $this->actingAs($user)->getJson('/v1/admin/activity-logs');

        activity()->causedBy($user)->log('Did a thing');

        $response = $this->actingAs($user)->getJson('/v1/admin/activity-logs')->assertOk();

        $this->assertSame('Rakib', $response->json('result.data.0.causer'));
    }

    public function test_it_reports_a_missing_causer_as_system(): void
    {
        Artisan::command('activitylog:probe', function () {
            activity()->log('From the console');
        });
        Artisan::call('activitylog:probe');

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/activity-logs')
            ->assertOk();

        $this->assertNull($response->json('result.data.0.causer'));
    }

    public function test_there_is_no_bulk_delete_route(): void
    {
        $log = $this->makeLog('Delete me');

        $this->actingAs(User::factory()->create())
            ->deleteJson('/v1/admin/activity-logs/bulk', ['ids' => [$log->id]])
            ->assertNotFound();

        $this->assertDatabaseHas('activity_logs', ['id' => $log->id]);
    }

    public function test_a_record_of_a_deleted_log_cannot_itself_be_deleted(): void
    {
        $log = $this->makeLog('Delete me');
        $user = User::factory()->create();

        $this->actingAs($user)->deleteJson("/v1/admin/activity-logs/{$log->id}")->assertOk();

        $trace = ActivityLog::query()->where('subject_type', ActivityLog::class)->sole();

        // The chain has to stop somewhere, or every delete spawns another row.
        $this->actingAs($user)
            ->deleteJson("/v1/admin/activity-logs/{$trace->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('activity_logs', ['id' => $trace->id]);
    }

    public function test_deleting_a_log_is_itself_logged(): void
    {
        $log = $this->makeLog('Delete me');

        $this->actingAs(User::factory()->create())
            ->deleteJson("/v1/admin/activity-logs/{$log->id}")
            ->assertOk();

        $this->assertDatabaseMissing('activity_logs', ['id' => $log->id]);

        // Removing audit history must leave its own trace.
        $trace = ActivityLog::query()->where('description', 'Deleted an activity log.')->sole();

        $this->assertSame(ActivityLog::class, $trace->subject_type);
        $this->assertSame($log->id, $trace->subject_id);
    }

    public function test_it_returns_filter_options(): void
    {
        $subject = User::factory()->create();
        activity()->performedOn($subject)->type('crm')->log('With subject');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/activity-logs/filters')
            ->assertOk()
            ->assertJsonStructure([
                'result' => [
                    'types' => [['value', 'label']],
                    'subject_types' => [['value', 'label']],
                ],
            ]);
    }

    public function test_staff_cannot_read_the_activity_log(): void
    {
        $this->seed(UserSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(Role::findByName('staff', 'web'));

        $this->actingAs($staff)->getJson('/v1/admin/activity-logs')->assertForbidden();
    }
}
