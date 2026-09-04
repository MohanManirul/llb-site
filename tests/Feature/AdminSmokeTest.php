<?php

namespace Tests\Feature;

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
            '/admin/roles', '/admin/roles/create', '/admin/activity-logs',
            '/admin/settings',
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
            '/v1/admin/roles/permission-groups',
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
}
