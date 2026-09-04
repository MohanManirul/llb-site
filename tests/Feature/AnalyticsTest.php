<?php

namespace Tests\Feature;

use App\Models\MaterialDownload;
use App\Models\Program;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\Models\User;
use App\Models\VisitorSession;
use App\Services\Analytics\VisitorTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function visitorCookie(string $id = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'): array
    {
        return [VisitorTrackingService::COOKIE => $id];
    }

    public function test_a_first_visit_sets_the_visitor_cookie(): void
    {
        $this->get('/bn')->assertOk()->assertCookie(VisitorTrackingService::COOKIE, null, false);
    }

    public function test_a_bot_gets_no_visitor_cookie(): void
    {
        $response = $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])->get('/bn');

        $response->assertOk();
        $this->assertEmpty(array_filter(
            $response->headers->getCookies(),
            fn ($cookie) => $cookie->getName() === VisitorTrackingService::COOKIE,
        ));
    }

    public function test_the_pulse_records_the_visitor_and_powers_the_live_count(): void
    {
        $this->withCredentials()
            ->withUnencryptedCookies($this->visitorCookie())
            ->postJson('/v1/public/pulse', ['path' => '/bn/browse'])
            ->assertOk();

        $this->assertSame(1, VisitorSession::count());

        $session = VisitorSession::first();
        $this->assertSame('/bn/browse', $session->last_path);
        $this->assertSame(1, $session->page_views);

        $live = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/reports/live')
            ->assertOk()
            ->json('result');

        $this->assertSame(1, $live['online_now']);
        $this->assertSame(1, $live['visitors_today']);
        $this->assertSame('/bn/browse', $live['top_pages'][0]['path']);
    }

    public function test_repeated_pulses_from_one_visitor_stay_one_session(): void
    {
        $this->withCredentials()
            ->withUnencryptedCookies($this->visitorCookie())
            ->postJson('/v1/public/pulse', ['path' => '/bn'])
            ->assertOk();

        $this->withCredentials()
            ->withUnencryptedCookies($this->visitorCookie())
            ->postJson('/v1/public/pulse')
            ->assertOk();

        $this->assertSame(1, VisitorSession::count());
        $this->assertSame(1, VisitorSession::first()->page_views);
    }

    public function test_a_pulse_without_a_cookie_or_from_a_bot_records_nothing(): void
    {
        $this->postJson('/v1/public/pulse', ['path' => '/bn'])->assertOk();

        $this->withCredentials()
            ->withHeaders(['User-Agent' => 'Googlebot/2.1'])
            ->withUnencryptedCookies($this->visitorCookie())
            ->postJson('/v1/public/pulse', ['path' => '/bn'])
            ->assertOk();

        $this->assertSame(0, VisitorSession::count());
    }

    public function test_an_idle_visitor_leaves_the_online_count(): void
    {
        VisitorSession::create([
            'visitor_id' => str_repeat('b', 40),
            'first_seen_at' => now()->subHour(),
            'last_seen_at' => now()->subMinutes(10),
            'page_views' => 3,
        ]);

        $live = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/reports/live')
            ->assertOk()
            ->json('result');

        $this->assertSame(0, $live['online_now']);
        $this->assertSame(1, $live['visitors_today']);
    }

    public function test_downloads_record_events_and_report_unique_people(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $program = Program::create([
            'slug' => 'bar-council', 'name_bn' => 'বার কাউন্সিল', 'name_en' => 'Bar Council',
        ]);
        $subject = Subject::create([
            'program_id' => $program->id, 'slug' => 'penal-code',
            'name_bn' => 'দণ্ডবিধি', 'name_en' => 'Penal Code',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/v1/admin/study-materials', [
                'type' => 'suggestion',
                'title_bn' => 'দণ্ডবিধি সাজেশন',
                'subject_id' => $subject->id,
                'content_language' => 'bn',
                'files' => [
                    ['file' => UploadedFile::fake()->create('one.pdf', 300, 'application/pdf')],
                ],
            ])
            ->assertCreated();

        $material = StudyMaterial::first();

        $this->actingAs($user)
            ->patchJson("/v1/admin/study-materials/{$material->id}/publish")
            ->assertOk();

        auth()->guard('web')->logout();

        $file = $material->files()->first();
        $url = "/v1/public/materials/{$material->slug}/files/{$file->id}/download";

        $this->withUnencryptedCookies($this->visitorCookie(str_repeat('c', 40)))->get($url)->assertOk();
        $this->withUnencryptedCookies($this->visitorCookie(str_repeat('c', 40)))->get($url)->assertOk();
        $this->withUnencryptedCookies($this->visitorCookie(str_repeat('d', 40)))->get($url)->assertOk();

        $this->assertSame(3, MaterialDownload::count());
        $this->assertNotNull(MaterialDownload::first()->ip_hash);

        $report = $this->actingAs($user)
            ->getJson('/v1/admin/reports/downloads')
            ->assertOk()
            ->json('result.data.0');

        $this->assertSame(3, $report['download_count']);
        $this->assertSame(3, $report['period_downloads']);
        $this->assertSame(2, $report['unique_visitors']);
        $this->assertNotNull($report['last_downloaded_at']);

        $files = $this->actingAs($user)
            ->getJson("/v1/admin/reports/downloads/{$material->id}/files")
            ->assertOk()
            ->json('result');

        $this->assertSame(3, $files[0]['download_count']);
        $this->assertSame(2, $files[0]['unique_visitors']);
    }

    public function test_the_reports_need_the_dashboard_permission(): void
    {
        $this->getJson('/v1/admin/reports/live')->assertUnauthorized();
    }

    public function test_the_prune_command_removes_stale_rows_but_keeps_counters(): void
    {
        VisitorSession::create([
            'visitor_id' => str_repeat('e', 40),
            'first_seen_at' => now()->subDays(60),
            'last_seen_at' => now()->subDays(45),
            'page_views' => 5,
        ]);
        VisitorSession::create([
            'visitor_id' => str_repeat('f', 40),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'page_views' => 1,
        ]);

        $this->artisan('llb:prune-analytics')->assertSuccessful();

        $this->assertSame(1, VisitorSession::count());
        $this->assertSame(str_repeat('f', 40), VisitorSession::first()->visitor_id);
    }
}
