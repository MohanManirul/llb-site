<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Notice;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiPublicNoticeTest extends TestCase
{
    use RefreshDatabase;

    private function makeNotice(array $overrides = []): Notice
    {
        return Notice::create(array_merge([
            'slug' => 'notice-'.uniqid(),
            'title_bn' => 'সাধারণ নোটিশ',
            'body_bn' => 'নোটিশের বিস্তারিত।',
            'category' => 'general',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ], $overrides));
    }

    public function test_drafts_and_future_notices_are_invisible(): void
    {
        $this->makeNotice(['status' => 'draft']);
        $this->makeNotice(['published_at' => now()->addDay()]);
        $visible = $this->makeNotice();

        $response = $this->getJson('/v1/public/notices')->assertOk();

        $this->assertSame(1, $response->json('result.meta.total'));
        $this->assertSame($visible->slug, $response->json('result.data.0.slug'));
    }

    public function test_an_expired_notice_leaves_the_list_but_its_page_still_opens(): void
    {
        $expired = $this->makeNotice(['expires_at' => now()->subDay()]);

        $this->getJson('/v1/public/notices')
            ->assertOk()
            ->assertJsonPath('result.meta.total', 0);

        $this->getJson("/v1/public/notices/{$expired->slug}")
            ->assertOk()
            ->assertJsonPath('result.slug', $expired->slug);
    }

    public function test_pinned_notices_sort_first(): void
    {
        $this->makeNotice(['title_bn' => 'পুরনো', 'published_at' => now()->subDays(2)]);
        $pinned = $this->makeNotice([
            'title_bn' => 'পিন করা',
            'is_pinned' => true,
            'published_at' => now()->subDays(5),
        ]);
        $this->makeNotice(['title_bn' => 'নতুন', 'published_at' => now()->subMinute()]);

        $this->getJson('/v1/public/notices')
            ->assertOk()
            ->assertJsonPath('result.data.0.slug', $pinned->slug);
    }

    public function test_a_program_scoped_notice_only_shows_for_its_program(): void
    {
        $hons = Program::create(['slug' => 'llb-hons', 'name_bn' => 'অনার্স', 'name_en' => 'Hons']);
        Program::create(['slug' => 'bjs', 'name_bn' => 'বিজেএস', 'name_en' => 'BJS']);

        $global = $this->makeNotice(['title_bn' => 'সবার জন্য']);
        $scoped = $this->makeNotice(['title_bn' => 'অনার্সের জন্য', 'program_id' => $hons->id]);

        $forHons = collect($this->getJson('/v1/public/notices?program=llb-hons')->json('result.data'))
            ->pluck('slug');

        $this->assertContains($global->slug, $forHons);
        $this->assertContains($scoped->slug, $forHons);

        $forBjs = collect($this->getJson('/v1/public/notices?program=bjs')->json('result.data'))
            ->pluck('slug');

        $this->assertContains($global->slug, $forBjs);
        $this->assertNotContains($scoped->slug, $forBjs);
    }

    public function test_a_session_scoped_notice_only_shows_for_its_session(): void
    {
        $session = AcademicSession::create([
            'slug' => '2024-25', 'label' => '2024-25', 'start_year' => 2024, 'end_year' => 2025,
        ]);
        AcademicSession::create([
            'slug' => '2025-26', 'label' => '2025-26', 'start_year' => 2025, 'end_year' => 2026,
        ]);

        $scoped = $this->makeNotice(['academic_session_id' => $session->id]);

        $this->assertContains(
            $scoped->slug,
            collect($this->getJson('/v1/public/notices?session=2024-25')->json('result.data'))->pluck('slug'),
        );

        $this->assertNotContains(
            $scoped->slug,
            collect($this->getJson('/v1/public/notices?session=2025-26')->json('result.data'))->pluck('slug'),
        );
    }

    public function test_the_body_returns_both_languages_with_fallback(): void
    {
        $notice = $this->makeNotice();

        $this->getJson("/v1/public/notices/{$notice->slug}")
            ->assertOk()
            ->assertJsonPath('result.body.bn', 'নোটিশের বিস্তারিত।')
            ->assertJsonPath('result.body.en', 'নোটিশের বিস্তারিত।');
    }

    public function test_the_attachment_downloads_and_counts(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/v1/admin/notices', [
                'title_bn' => 'রুটিন প্রকাশ',
                'body_bn' => 'নতুন রুটিন সংযুক্ত।',
                'category' => 'routine',
                'is_pinned' => false,
                'attachment' => UploadedFile::fake()->create('routine.pdf', 300, 'application/pdf'),
            ])
            ->assertCreated();

        $notice = Notice::first();

        $this->actingAs($user)
            ->patchJson("/v1/admin/notices/{$notice->id}/publish")
            ->assertOk();

        auth()->guard('web')->logout();

        $this->get("/v1/public/notices/{$notice->slug}/attachment")->assertOk();

        $this->assertSame(1, $notice->refresh()->attachment_download_count);
    }

    public function test_a_draft_notices_attachment_cannot_be_downloaded(): void
    {
        Storage::fake('local');

        $notice = $this->makeNotice([
            'status' => 'draft',
            'attachment_disk' => 'local',
            'attachment_path' => 'uploads/notices/x.pdf',
            'attachment_name' => 'x.pdf',
        ]);

        $this->getJson("/v1/public/notices/{$notice->slug}/attachment")->assertNotFound();
    }

    public function test_staff_can_draft_but_not_publish_notices(): void
    {
        $this->seed(UserSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(UserSeeder::STAFF);

        $this->actingAs($staff)
            ->postJson('/v1/admin/notices', [
                'title_bn' => 'খসড়া নোটিশ',
                'body_bn' => 'বিস্তারিত।',
                'category' => 'general',
                'is_pinned' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('result.status', 'draft');

        $notice = Notice::first();

        $this->actingAs($staff)
            ->patchJson("/v1/admin/notices/{$notice->id}/publish")
            ->assertForbidden();
    }
}
