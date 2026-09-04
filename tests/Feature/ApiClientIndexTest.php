<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers what Clients/Index.jsx relies on now that the page carries no server
 * props: the API has to do the searching, filtering, sorting and paginating
 * that the old Inertia controller did — and it has to hand back `image`, or
 * the table's view modal has no picture to show.
 */
class ApiClientIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * clients.phone is NOT NULL and required by validation, so every row needs
     * one — a counter keeps it (and email) unique across a test.
     */
    private int $sequence = 0;

    private function makeClient(string $name, bool $status = true, ?string $image = null): Client
    {
        $this->sequence++;

        return Client::create([
            'name' => $name,
            'email' => str_replace(' ', '', strtolower($name)).$this->sequence.'@example.com',
            'phone' => '0170000'.str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT),
            'is_active' => $status,
            'image' => $image,
            'password' => 'secret123',
        ]);
    }

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/v1/admin/clients')->assertUnauthorized();
    }

    public function test_it_returns_a_paginator_the_datatable_can_render(): void
    {
        $this->makeClient('Acme Corp');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/clients')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'result' => [
                    'data' => [[
                        'id', 'name', 'email', 'phone', 'address',
                        'image_url', 'thumbnail_url', 'description', 'is_active', 'created_at',
                    ]],
                    'links' => ['prev', 'next'],
                    'meta' => ['current_page', 'from', 'to', 'per_page'],
                ],
            ]);
    }

    /** Without the image urls on the row, the view modal renders a broken picture. */
    public function test_a_row_carries_the_image_urls(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('clients/globex.png', 'fake-bytes');

        $this->makeClient('Globex', image: 'clients/globex.png');

        $url = Storage::disk('public')->url('clients/globex.png');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/clients')
            ->assertOk()
            ->assertJsonPath('result.data.0.image_url', $url)
            ->assertJsonPath('result.data.0.thumbnail_url', $url);
    }

    public function test_it_searches_by_name_email_and_phone(): void
    {
        $acme = $this->makeClient('Acme Corp');
        $this->makeClient('Globex');

        $user = User::factory()->create();

        foreach ([$acme->name, $acme->email, $acme->phone] as $term) {
            $this->actingAs($user)
                ->getJson('/v1/admin/clients?search='.urlencode($term))
                ->assertOk()
                ->assertJsonCount(1, 'result.data')
                ->assertJsonPath('result.data.0.name', 'Acme Corp');
        }
    }

    public function test_it_filters_by_status(): void
    {
        $this->makeClient('Active Co');
        $this->makeClient('Inactive Co', status: false);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/v1/admin/clients?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Inactive Co');

        $this->actingAs($user)
            ->getJson('/v1/admin/clients?is_active=1')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Active Co');

        // Empty filter = "no filter", so both rows come back.
        $this->actingAs($user)
            ->getJson('/v1/admin/clients?is_active=')
            ->assertOk()
            ->assertJsonCount(2, 'result.data');
    }

    public function test_it_sorts_by_a_whitelisted_column(): void
    {
        $this->makeClient('Zulu');
        $this->makeClient('Alpha');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/v1/admin/clients?sort=name&direction=asc')
            ->assertOk()
            ->assertJsonPath('result.data.0.name', 'Alpha');

        $this->actingAs($user)
            ->getJson('/v1/admin/clients?sort=name&direction=desc')
            ->assertOk()
            ->assertJsonPath('result.data.0.name', 'Zulu');
    }

    public function test_it_honours_per_page(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->makeClient("Client {$i}");
        }

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/clients?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'result.data')
            ->assertJsonPath('result.meta.per_page', 2)
            ->assertJsonPath('result.meta.current_page', 1);
    }

    public function test_it_soft_deletes_a_client(): void
    {
        $keep = $this->makeClient('Keep Co');
        $drop = $this->makeClient('Drop One');

        $this->actingAs(User::factory()->create())
            ->deleteJson("/v1/admin/clients/{$drop->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        // Soft delete — the rows stay put with a deleted_at stamp.
        $this->assertSoftDeleted('clients', ['id' => $drop->id]);
        $this->assertDatabaseHas('clients', ['id' => $keep->id, 'deleted_at' => null]);
    }

    /**
     * A soft-deleted client must keep its picture — otherwise a restore would
     * come back with a broken image.
     */
    public function test_a_soft_deleted_client_keeps_its_image_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('clients/keepme.png', 'fake-bytes');

        $client = $this->makeClient('Globex', image: 'clients/keepme.png');

        $this->actingAs(User::factory()->create())
            ->deleteJson("/v1/admin/clients/{$client->id}")
            ->assertOk();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
        Storage::disk('public')->assertExists('clients/keepme.png');
    }
}
