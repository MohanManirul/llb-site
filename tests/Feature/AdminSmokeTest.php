<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_page_renders(): void
    {
        $user = User::factory()->create();
        $pages = [
            '/admin', '/admin/dashboard', '/admin/profile', '/admin/users', '/admin/users/create',
            '/admin/roles', '/admin/roles/create', '/admin/companies', '/admin/departments',
            '/admin/designations', '/admin/activity-logs', '/admin/employees', '/admin/employees/create',
            '/admin/teams', '/admin/teams/create', '/admin/clients', '/admin/clients/create',
            '/admin/projects', '/admin/projects/create', '/admin/settings',
        ];
        $bad = [];
        foreach ($pages as $p) {
            $s = $this->actingAs($user)->get($p)->getStatusCode();
            if (! in_array($s, [200, 302], true)) {
                $bad[] = "$p => $s";
            }
        }
        $this->assertSame([], $bad, "admin pages failing:\n".implode("\n", $bad));
    }

    public function test_every_admin_data_endpoint_responds(): void
    {
        $user = User::factory()->create();
        $endpoints = [
            '/v1/admin/me', '/v1/admin/dashboard', '/v1/admin/dashboard/report', '/v1/profile', '/v1/notifications',
            '/v1/admin/users', '/v1/admin/users/search?q=a', '/v1/admin/users/roles', '/v1/admin/roles',
            '/v1/admin/roles/permission-groups', '/v1/admin/companies', '/v1/admin/companies/search',
            '/v1/admin/departments', '/v1/admin/departments/search', '/v1/admin/designations', '/v1/admin/designations/search',
            '/v1/admin/employees', '/v1/admin/employees/search', '/v1/admin/teams', '/v1/admin/teams/search',
            '/v1/admin/clients', '/v1/admin/projects', '/v1/admin/projects/teams/search',
            '/v1/admin/projects/clients/search',
            '/v1/admin/projects/companies/search', '/v1/admin/projects/departments/search',
            '/v1/admin/activity-logs', '/v1/admin/activity-logs/filters',
        ];
        $bad = [];
        foreach ($endpoints as $e) {
            $s = $this->actingAs($user)->getJson($e)->getStatusCode();
            if ($s !== 200) {
                $bad[] = "$e => $s";
            }
        }
        $this->assertSame([], $bad, "admin endpoints failing:\n".implode("\n", $bad));
    }

    /** my-teams is employee-scoped, so a staff account without one is refused. */
    public function test_my_teams_is_employee_only(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/my-teams')
            ->assertForbidden();
    }

    public function test_the_client_portal_pages_and_endpoints_respond(): void
    {
        $client = Client::create([
            'name' => 'Globex Ltd',
            'code' => 'GLX',
            'email' => 'glx@example.com',
            'phone' => '01700000001',
            'is_active' => true,
            'is_active' => true,
            'password' => 'portal-password',
        ]);

        $bad = [];

        foreach (['/dashboard', '/profile', '/projects'] as $page) {
            $status = $this->actingAs($client, 'client-web')->get($page)->getStatusCode();
            if ($status !== 200) {
                $bad[] = "$page => $status";
            }
        }

        // Every endpoint the portal's pages actually call, including the option
        // list ClientProjectsTable needs for its status filter.
        $endpoints = [
            '/v1/client/me',
            '/v1/client/dashboard',
            '/v1/client/dashboard/report',
            '/v1/profile',
            '/v1/client/projects',
            '/v1/notifications',

        ];

        foreach ($endpoints as $endpoint) {
            $status = $this->actingAs($client, 'client-web')->getJson($endpoint)->getStatusCode();
            if ($status !== 200) {
                $bad[] = "$endpoint => $status";
            }
        }

        $this->assertSame([], $bad, "portal failing:\n".implode("\n", $bad));
    }

    /** The portal lists and views projects only — it never creates or edits. */
    public function test_the_portal_has_no_project_write_routes(): void
    {
        $client = Client::create([
            'name' => 'Globex Ltd',
            'code' => 'GLX2',
            'email' => 'glx2@example.com',
            'phone' => '01700000009',
            'is_active' => true,
            'is_active' => true,
            'password' => 'portal-password',
        ]);

        // No portal page for either.
        $this->actingAs($client, 'client-web')->get('/projects/create')->assertNotFound();

        $this->actingAs($client, 'client-web')
            ->postJson('/v1/client/projects', ['business_name' => 'Nope'])
            ->assertMethodNotAllowed();

        $this->actingAs($client, 'client-web')
            ->postJson('/v1/admin/projects', ['business_name' => 'Nope'])
            ->assertUnauthorized();
    }

    public function test_the_portal_guest_pages_render(): void
    {
        $bad = [];

        foreach (['/login', '/forgot-password', '/reset-password/some-token'] as $page) {
            $status = $this->get($page)->getStatusCode();
            if ($status !== 200) {
                $bad[] = "$page => $status";
            }
        }

        $this->assertSame([], $bad, "portal guest pages failing:\n".implode("\n", $bad));
    }
}
