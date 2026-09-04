<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_staff_login_records_exactly_one_activity_row(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'password123']);

        $this->postJson('/v1/login', ['email' => 'a@example.com', 'password' => 'password123'])
            ->assertOk();

        $this->assertSame(1, DB::table('activity_logs')->where('description', 'Signed in.')->count());
    }

    public function test_a_client_login_records_exactly_one_activity_row(): void
    {
        Client::create([
            'name' => 'Globex',
            'email' => 'g@example.com',
            'phone' => '01711000001',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        $this->postJson('/v1/client/login', ['email' => 'g@example.com', 'password' => 'secret123'])
            ->assertOk();

        $this->assertSame(1, DB::table('activity_logs')->where('description', 'Client signed in.')->count());
    }

    public function test_client_crud_records_one_row_per_action(): void
    {
        $user = User::factory()->create();
        $payload = [
            'name' => 'Globex', 'email' => 'c@example.com', 'phone' => '01711000002',
            'is_active' => true, 'password' => 'secret123', 'password_confirmation' => 'secret123',
        ];

        $this->actingAs($user)->postJson('/v1/admin/clients', $payload)->assertCreated();
        $client = Client::firstWhere('email', 'c@example.com');

        $this->actingAs($user)->putJson("/v1/admin/clients/{$client->id}", [...$payload, 'name' => 'Renamed'])->assertOk();
        $this->actingAs($user)->deleteJson("/v1/admin/clients/{$client->id}")->assertOk();

        foreach (['Client created.', 'Client updated.', 'Client deleted.'] as $description) {
            $this->assertSame(1, DB::table('activity_logs')->where('description', $description)->count());
        }
    }
}
