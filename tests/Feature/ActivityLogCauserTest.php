<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ActivityLogCauserTest extends TestCase
{
    use RefreshDatabase;

    private function defineProbeRoute(): void
    {
        Route::middleware('auth:sanctum')->get('/_activity_probe', function () {
            activity()->log('probe');

            return response()->noContent();
        });
    }

    public function test_it_writes_the_subject_type_and_description(): void
    {
        $company = Company::create(['name' => 'Acme Corp', 'code' => 'ACME', 'is_active' => true]);

        $log = activity()->performedOn($company)->type('crm')->log('Company created');

        $this->assertSame('crm', $log->type);
        $this->assertSame('Company created', $log->description);
        $this->assertSame(Company::class, $log->subject_type);
        $this->assertSame($company->id, $log->subject_id);
    }

    public function test_it_defaults_the_type_to_admin(): void
    {
        $this->assertSame('admin', activity()->log('probe')->type);
    }

    public function test_it_records_the_causer_on_a_session_authenticated_request(): void
    {
        $this->defineProbeRoute();

        $user = User::factory()->create();

        $this->actingAs($user)->get('/_activity_probe')->assertNoContent();

        $log = ActivityLog::query()->where('description', 'probe')->sole();

        $this->assertSame($user->id, $log->causer_id);
        $this->assertSame(User::class, $log->causer_type);
    }

    public function test_it_records_the_causer_on_a_bearer_token_request(): void
    {
        $this->defineProbeRoute();

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/_activity_probe')
            ->assertNoContent();

        // Reading the default guard, as the reference project does, would leave
        // this NULL.
        $this->assertSame($user->id, ActivityLog::query()->sole()->causer_id);
    }

    public function test_it_saves_with_a_null_causer_from_the_console(): void
    {
        Artisan::command('activitylog:probe', function () {
            activity()->log('probe from console');
        });

        Artisan::call('activitylog:probe');

        $this->assertNull(ActivityLog::query()->sole()->causer_id);
    }

    public function test_it_accepts_an_explicit_causer(): void
    {
        $user = User::factory()->create();

        $log = activity()->causedBy($user)->log('probe');

        $this->assertSame($user->id, $log->causer_id);
    }
}
