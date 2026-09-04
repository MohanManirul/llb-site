<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Departments web route is a page shell only — no data, no controller.
 * Creating and editing happen in a modal on that same page, so there are no
 * create/edit routes to cover. These tests pin the two things that can still
 * break: the page renders, and `auth:sanctum` still redirects a browser guest
 * to /login rather than answering with a 401.
 */
class DepartmentPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_department_page_renders_without_server_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/departments')->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/departments')->assertRedirect('/admin/login');
    }
}
