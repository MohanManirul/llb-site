<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCollegeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/v1/admin/colleges')->assertUnauthorized();
    }

    public function test_colleges_are_listed_with_search_and_active_filter(): void
    {
        College::factory()->create(['name_bn' => 'Dhaka Law College', 'name_en' => 'Dhaka Law College']);
        College::factory()->inactive()->create(['name_bn' => 'Khulna Law College', 'name_en' => 'Khulna Law College']);

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->getJson('/v1/admin/colleges')
            ->assertOk()
            ->assertJsonCount(2, 'result.data');

        $this->actingAs($admin)
            ->getJson('/v1/admin/colleges?search=Dhaka')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name_en', 'Dhaka Law College');

        $this->actingAs($admin)
            ->getJson('/v1/admin/colleges?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name_en', 'Khulna Law College');
    }

    public function test_a_college_can_be_created_updated_and_deleted(): void
    {
        $admin = User::factory()->create();

        $created = $this->actingAs($admin)
            ->postJson('/v1/admin/colleges', [
                'name_bn' => 'Sylhet Law College',
                'name_en' => 'Sylhet Law College',
                'district_en' => 'Sylhet',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('result.name_en', 'Sylhet Law College')
            ->json('result.id');

        $this->assertDatabaseHas('colleges', ['name_en' => 'Sylhet Law College']);

        $this->actingAs($admin)
            ->putJson("/v1/admin/colleges/{$created}", [
                'name_bn' => 'Sylhet Law College',
                'name_en' => 'Sylhet Law College',
                'district_en' => 'Sylhet Sadar',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('result.district_en', 'Sylhet Sadar')
            ->assertJsonPath('result.is_active', false);

        $this->actingAs($admin)
            ->deleteJson("/v1/admin/colleges/{$created}")
            ->assertOk();

        $this->assertDatabaseMissing('colleges', ['id' => $created]);
    }

    public function test_a_created_college_gets_a_unique_slug(): void
    {
        $admin = User::factory()->create();

        $payload = ['name_bn' => 'Demo Law College', 'name_en' => 'Demo Law College', 'is_active' => true];

        $first = $this->actingAs($admin)->postJson('/v1/admin/colleges', $payload)->json('result.slug');
        $second = $this->actingAs($admin)->postJson('/v1/admin/colleges', $payload)->json('result.slug');

        $this->assertNotSame($first, $second);
    }

    public function test_staff_role_cannot_delete_a_college(): void
    {
        $this->seed(UserSeeder::class);

        $staff = User::factory()->create();
        $staff->syncRoles(UserSeeder::STAFF);

        $college = College::factory()->create();

        $this->actingAs($staff)
            ->deleteJson("/v1/admin/colleges/{$college->id}")
            ->assertForbidden();
    }

    public function test_the_public_college_list_only_returns_active_colleges(): void
    {
        College::factory()->create(['name_en' => 'Active College', 'name_bn' => 'Active College']);
        College::factory()->inactive()->create(['name_en' => 'Hidden College', 'name_bn' => 'Hidden College']);

        $response = $this->getJson('/v1/public/colleges')->assertOk();

        $labels = collect($response->json('result'))->pluck('label');

        $this->assertTrue($labels->contains('Active College'));
        $this->assertFalse($labels->contains('Hidden College'));
    }

    public function test_the_public_college_list_labels_follow_the_requested_locale(): void
    {
        College::factory()->create([
            'name_bn' => 'ঢাকা ল কলেজ',
            'name_en' => 'Dhaka Law College',
            'district_bn' => 'ঢাকা',
            'district_en' => 'Dhaka',
        ]);

        $bn = $this->getJson('/v1/public/colleges?locale=bn')->assertOk();
        $this->assertSame('ঢাকা ল কলেজ', $bn->json('result.0.label'));
        $this->assertSame('ঢাকা', $bn->json('result.0.district.bn'));

        $en = $this->getJson('/v1/public/colleges?locale=en')->assertOk();
        $this->assertSame('Dhaka Law College', $en->json('result.0.label'));
        $this->assertSame('Dhaka', $en->json('result.0.district.en'));
    }

    public function test_the_public_college_list_is_alphabetical_with_bengali_vowels_before_consonants(): void
    {
        College::factory()->create(['name_bn' => 'হবিগঞ্জ ল কলেজ', 'name_en' => 'Habiganj Law College', 'sort_order' => 1]);
        College::factory()->create(['name_bn' => 'কুমিল্লা ল কলেজ', 'name_en' => 'Comilla Law College', 'sort_order' => 2]);
        College::factory()->create(['name_bn' => 'আইডিয়াল ল কলেজ', 'name_en' => 'Ideal Law College', 'sort_order' => 3]);

        $bn = collect($this->getJson('/v1/public/colleges?locale=bn')->assertOk()->json('result'))->pluck('label');

        $this->assertSame(['আইডিয়াল ল কলেজ', 'কুমিল্লা ল কলেজ', 'হবিগঞ্জ ল কলেজ'], $bn->all());

        $en = collect($this->getJson('/v1/public/colleges?locale=en')->assertOk()->json('result'))->pluck('label');

        $this->assertSame(['Comilla Law College', 'Habiganj Law College', 'Ideal Law College'], $en->all());
    }

    public function test_the_public_college_list_falls_back_when_a_name_is_missing_for_the_locale(): void
    {
        College::factory()->create(['name_bn' => 'ঢাকা ল কলেজ', 'name_en' => null]);

        $this->getJson('/v1/public/colleges?locale=en')
            ->assertOk()
            ->assertJsonPath('result.0.label', 'ঢাকা ল কলেজ');
    }
}
