<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiSiteSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_the_migration_seeds_the_row_the_app_reads(): void
    {
        $setting = SiteSetting::current();

        $this->assertSame('আইন পথ', $setting->name_bn);
        $this->assertSame('AinPath', $setting->name_en);
        $this->assertSame('/llb.jpg', $setting->logo_url);
    }

    public function test_staff_without_the_permission_cannot_read_or_write_the_settings(): void
    {
        $this->seed(UserSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(UserSeeder::STAFF);

        $this->actingAs($staff)->getJson('/v1/admin/site-settings')->assertForbidden();
        $this->actingAs($staff)->postJson('/v1/admin/site-settings', ['name_bn' => 'ক'])->assertForbidden();
    }

    public function test_an_admin_can_read_the_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/site-settings')
            ->assertOk()
            ->assertJsonPath('result.name_en', 'AinPath')
            ->assertJsonPath('result.whatsapp_url', null);
    }

    public function test_saving_updates_the_row_and_refreshes_the_cached_copy(): void
    {
        $this->assertSame('আইন পথ', SiteSetting::current()->name_bn);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/site-settings', [
                'name_bn' => 'আইন পথ বিডি',
                'name_en' => 'AinPath BD',
                'slogan_bn' => 'আইন শেখার সঙ্গী',
                'whatsapp_url' => 'https://chat.whatsapp.com/abcdef',
                'facebook_url' => 'https://facebook.com/groups/ainpath',
                'email' => 'hello@ainpath.test',
                'phone' => '01712345678',
            ])
            ->assertOk()
            ->assertJsonPath('result.name_en', 'AinPath BD');

        $this->assertDatabaseHas('site_settings', [
            'id' => SiteSetting::ROW_ID,
            'name_bn' => 'আইন পথ বিডি',
            'email' => 'hello@ainpath.test',
        ]);

        $this->assertSame('আইন পথ বিডি', SiteSetting::current()->name_bn);
    }

    public function test_saving_rejects_a_link_that_is_not_a_url(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/site-settings', [
                'name_bn' => 'আইন পথ',
                'whatsapp_url' => 'join our group',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['whatsapp_url']);
    }

    public function test_uploading_a_logo_stores_it_and_keeps_the_favicon(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/v1/admin/site-settings', [
                'name_bn' => 'আইন পথ',
                'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
            ])
            ->assertOk();

        $setting = SiteSetting::current();

        $this->assertStringStartsWith('uploads/images/', $setting->logo);
        $this->assertSame('/llb_favicon.png', $setting->favicon);
        Storage::disk(config('filesystems.default'))->assertExists($setting->logo);
    }

    public function test_the_branding_is_shared_with_every_public_page(): void
    {
        $this->get('/bn/exam-prep')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('site.name.bn', 'আইন পথ')
                ->where('site.logo_url', '/llb.jpg')
                ->where('site.whatsapp_url', null));
    }
}
