<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * What the topbar bell needs: the signed-in user's own notifications, an unread
 * count for the badge, and read/read-all endpoints that never touch another
 * user's rows.
 */
class ApiNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function notify(User $user, string $status = 'completed'): void
    {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'general',
            'data' => [
                'type' => 'general',
                'title' => 'Account notice',
                'status' => $status,
                'link' => '/admin/profile',
                'message' => $status === 'completed'
                    ? 'Your account was updated.'
                    : 'The account update failed.',
                'summary' => 'Profile details changed.',
            ],
        ]);
    }

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/v1/notifications')->assertUnauthorized();
    }

    public function test_it_lists_only_the_users_own_notifications_with_an_unread_count(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->notify($user);
        $this->notify($other);

        $this->actingAs($user)
            ->getJson('/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'result.notifications')
            ->assertJsonPath('result.unread_count', 1)
            ->assertJsonPath('result.notifications.0.kind', 'general')
            ->assertJsonPath('result.notifications.0.title', 'Account notice')
            ->assertJsonPath('result.notifications.0.link', '/admin/profile')
            ->assertJsonPath('result.notifications.0.read', false)
            ->assertJsonPath(
                'result.notifications.0.message',
                'Your account was updated.'
            )
            ->assertJsonPath(
                'result.notifications.0.summary',
                'Profile details changed.'
            );
    }

    public function test_it_marks_one_notification_as_read(): void
    {
        $user = User::factory()->create();
        $this->notify($user);

        $notification = $user->notifications()->sole();

        $this->actingAs($user)
            ->patchJson("/v1/notifications/{$notification->id}/read")
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_it_refuses_to_mark_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->notify($owner);
        $notification = $owner->notifications()->sole();

        $this->actingAs($other)
            ->patchJson("/v1/notifications/{$notification->id}/read")
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_it_marks_every_notification_as_read(): void
    {
        $user = User::factory()->create();
        $this->notify($user);
        $this->notify($user, 'failed');

        $this->actingAs($user)
            ->patchJson('/v1/notifications/read-all')
            ->assertOk();

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertSame(2, $user->notifications()->count());
    }
}
