<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEntryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_hitting_the_admin_root_lands_on_the_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_a_signed_in_user_hitting_the_admin_root_lands_on_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertRedirect('/admin/dashboard');
    }
}
