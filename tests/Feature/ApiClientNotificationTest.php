<?php

namespace Tests\Feature;

use App\Enums\PaymentNotificationType;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The portal's own notification inbox, served from the framework's
 * `notifications` table — the same rows the payment reminders write.
 */
class ApiClientNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private Client $otherClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->makeClient('Globex', 'GLX', 'glx@example.com', '01700000001');
        $this->otherClient = $this->makeClient('Initech', 'INT', 'int@example.com', '01700000002');
    }

    private function makeClient(string $name, string $code, string $email, string $phone): Client
    {
        return Client::create([
            'name' => $name,
            'code' => $code,
            'email' => $email,
            'phone' => $phone,
            'is_active' => true,
            'password' => 'portal-password',
        ]);
    }

    private function notify(Client $client, string $message = 'Payment is due'): string
    {
        $id = (string) Str::uuid();

        $client->notifications()->create([
            'id' => $id,
            'type' => 'App\Notifications\PaymentDueReminder',
            'data' => [
                'type' => PaymentNotificationType::PaymentDueReminder->value,
                'status' => 'pending',
                'message' => $message,
                'summary' => 'Due in 3 days',
                'link' => '/projects/1',
                'project_id' => 1,
                'client_id' => $client->id,
                'due_date' => '2026-09-01',
                'amount' => 2500.0,
            ],
        ]);

        return $id;
    }

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/v1/client/notifications')->assertUnauthorized();
    }

    public function test_it_lists_only_the_clients_own_notifications_with_an_unread_count(): void
    {
        $this->notify($this->client);
        $this->notify($this->otherClient, 'Not yours');

        $this->actingAs($this->client, 'client-web')
            ->getJson('/v1/client/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'result.notifications')
            ->assertJsonPath('result.unread_count', 1)
            ->assertJsonPath('result.notifications.0.kind', 'payment_due_reminder')
            ->assertJsonPath('result.notifications.0.title', 'Upcoming payment')
            ->assertJsonPath('result.notifications.0.message', 'Payment is due')
            ->assertJsonPath('result.notifications.0.link', '/projects/1')
            ->assertJsonPath('result.notifications.0.due_date', '2026-09-01')
            ->assertJsonPath('result.notifications.0.read', false)
            ->assertJsonPath('result.notifications.0.read_at', null);
    }

    public function test_it_marks_one_notification_as_read(): void
    {
        $id = $this->notify($this->client);

        $this->actingAs($this->client, 'client-web')
            ->patchJson("/v1/client/notifications/{$id}/read")
            ->assertOk();

        $this->assertNotNull($this->client->notifications()->sole()->read_at);
        $this->assertSame(0, $this->client->unreadNotifications()->count());
    }

    public function test_it_refuses_to_mark_another_clients_notification(): void
    {
        $id = $this->notify($this->otherClient);

        $this->actingAs($this->client, 'client-web')
            ->patchJson("/v1/client/notifications/{$id}/read")
            ->assertForbidden();

        $this->assertNull($this->otherClient->notifications()->sole()->read_at);
    }

    public function test_it_marks_every_notification_as_read(): void
    {
        $this->notify($this->client, 'One');
        $this->notify($this->client, 'Two');

        $this->actingAs($this->client, 'client-web')
            ->patchJson('/v1/client/notifications/read-all')
            ->assertOk();

        $this->assertSame(0, $this->client->unreadNotifications()->count());
        $this->assertSame(2, $this->client->notifications()->count());
    }

    public function test_it_deletes_a_notification(): void
    {
        $id = $this->notify($this->client);

        $this->actingAs($this->client, 'client-web')
            ->deleteJson("/v1/client/notifications/{$id}")
            ->assertOk();

        $this->assertSame(0, $this->client->notifications()->count());
    }

    public function test_it_refuses_to_delete_another_clients_notification(): void
    {
        $id = $this->notify($this->otherClient);

        $this->actingAs($this->client, 'client-web')
            ->deleteJson("/v1/client/notifications/{$id}")
            ->assertForbidden();

        $this->assertSame(1, $this->otherClient->notifications()->count());
    }
}
