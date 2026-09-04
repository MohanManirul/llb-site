<?php

namespace Tests\Feature;

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
}
