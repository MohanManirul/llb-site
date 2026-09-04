<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Teams web routes are page shells only — no data, no controller.
 * These tests pin the two things that can still break: the pages render, and
 * `auth:sanctum` still redirects a browser guest to /login rather than
 * answering with a 401.
 */
class TeamPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_team_pages_render_without_server_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/teams')->assertOk();
        // /teams/create must not be swallowed by the /teams/{team} show route.
        $this->actingAs($user)->get('/admin/teams/create')->assertOk();

        // The edit/show pages only need the id from the URL; the record itself
        // is fetched from the API, so an unknown id still renders the shell.
        $this->actingAs($user)->get('/admin/teams/7/edit')->assertOk();
        $this->actingAs($user)->get('/admin/teams/7')->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/teams')->assertRedirect('/admin/login');
    }
}
