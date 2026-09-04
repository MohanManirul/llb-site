<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Clients web routes are page shells only — no data, no controller.
 * These tests pin the two things that can still break: the pages render, and
 * `auth:sanctum` still redirects a browser guest to /login rather than
 * answering with a 401.
 */
class ClientPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_client_pages_render_without_server_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/clients')->assertOk();
        $this->actingAs($user)->get('/admin/clients/create')->assertOk();

        // The edit page only needs the id from the URL; the record itself is
        // fetched from the API, so an unknown id still renders the shell.
        $this->actingAs($user)->get('/admin/clients/7/edit')->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/clients')->assertRedirect('/admin/login');
    }
}
