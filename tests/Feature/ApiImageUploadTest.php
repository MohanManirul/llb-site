<?php

namespace Tests\Feature;

use App\Models\User;
use App\Utilities\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Every user row in the database had a NULL image even though the
 * forms offered an upload — so the picture had nothing to show in the edit form
 * or the view modal. These tests pin the upload actually landing on the public
 * disk AND the path reaching the column.
 *
 * The person's photo lives on `users` now, so it is uploaded from the user form
 * off the shared upload trait.
 */
class ApiImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_image_is_stored_and_recorded(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->post('/v1/admin/users', [
                'name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'image' => UploadedFile::fake()->create('ada.jpg', 100, 'image/jpeg'),
            ])
            ->assertCreated();

        $ada = User::firstWhere('email', 'ada@example.com');

        $this->assertNotNull($ada->image, 'The image path was not saved.');
        $this->assertStringStartsWith('uploads/images/', $ada->image);
        Storage::disk('public')->assertExists($ada->image);
    }

    /** Replacing a photo removes the file it replaced. */
    public function test_replacing_a_user_image_deletes_the_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('users/old.jpg', 'fake-bytes');

        $user = User::factory()->create(['image' => 'users/old.jpg']);

        $this->actingAs(User::factory()->create())
            ->post("/v1/admin/users/{$user->id}", [
                '_method' => 'put',
                'name' => $user->name,
                'email' => $user->email,
                'image' => UploadedFile::fake()->create('new.jpg', 100, 'image/jpeg'),
            ])
            ->assertOk();

        $user->refresh();

        $this->assertNotSame('users/old.jpg', $user->image);
        Storage::disk('public')->assertExists($user->image);
        Storage::disk('public')->assertMissing('users/old.jpg');
    }

    public function test_remove_image_clears_the_photo_and_the_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('users/old.jpg', 'fake-bytes');

        $user = User::factory()->create(['image' => 'users/old.jpg']);

        $this->actingAs(User::factory()->create())
            ->putJson("/v1/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'remove_image' => '1',
            ])
            ->assertOk()
            ->assertJsonPath('result.image_url', null);

        $this->assertNull($user->fresh()->image);
        Storage::disk('public')->assertMissing('users/old.jpg');
    }

    /** The generated thumbnail lands beside the original. */
    public function test_an_uploaded_image_also_stores_a_thumbnail(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->post('/v1/admin/users', [
                'name' => 'Grace Hopper',
                'email' => 'grace@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'image' => UploadedFile::fake()->image('grace.png'),
            ])
            ->assertCreated();

        $grace = User::firstWhere('email', 'grace@example.com');

        Storage::disk('public')->assertExists($grace->image);
        Storage::disk('public')->assertExists(Asset::buildThumbnailPath($grace->image));
    }

    /** Replacing a photo takes its generated thumbnail with it. */
    public function test_replacing_an_image_deletes_the_old_thumbnail(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/v1/admin/users/{$user->id}", [
                '_method' => 'put',
                'name' => $user->name,
                'email' => $user->email,
                'image' => UploadedFile::fake()->image('first.png'),
            ])
            ->assertOk();

        $first = $user->fresh()->image;

        $this->actingAs(User::factory()->create())
            ->post("/v1/admin/users/{$user->id}", [
                '_method' => 'put',
                'name' => $user->name,
                'email' => $user->email,
                'image' => UploadedFile::fake()->image('second.png'),
            ])
            ->assertOk();

        $second = $user->fresh()->image;

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertMissing(Asset::buildThumbnailPath($first));
        Storage::disk('public')->assertExists(Asset::buildThumbnailPath($second));
    }

    /** The edit form reads the image back off the show endpoint. */
    public function test_the_show_endpoint_returns_the_image_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('users/example.png', 'fake-bytes');

        $user = User::factory()->create(['image' => 'users/example.png']);

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('result.image_url', Storage::disk('public')->url('users/example.png'));
    }
}
