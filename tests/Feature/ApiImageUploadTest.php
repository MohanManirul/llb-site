<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use App\Utilities\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Every client and user row in the database had a NULL image even though the
 * forms offered an upload — so the picture had nothing to show in the edit form
 * or the view modal. These tests pin the upload actually landing on the public
 * disk AND the path reaching the column.
 *
 * The person's photo lives on `users` now, so it is uploaded from the user form
 * and an employee simply reads it back off its user.
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

    /** The employee row reads its photo straight off the linked user. */
    public function test_an_employee_shows_its_users_image(): void
    {
        Storage::fake('public');

        $company = Company::create([
            'name' => 'Acme', 'code' => 'ACME',
            'email' => 'acme@example.com', 'is_active' => true,
        ]);
        $department = Department::create([
            'company_id' => $company->id, 'name' => 'Engineering',
            'code' => 'ENG', 'is_active' => true,
        ]);
        $designation = Designation::create(['name' => 'Engineer', 'is_active' => true]);
        Storage::disk('public')->put('users/ada.jpg', 'fake-bytes');

        $ada = User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'image' => 'users/ada.jpg',
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/employees', [
                'user_id' => $ada->id,
                'company_id' => $company->id,
                'department_id' => $department->id,
                'designation_id' => $designation->id,
                'is_active' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('result.image_url', Storage::disk('public')->url('users/ada.jpg'));

        $employee = Employee::firstWhere('user_id', $ada->id);

        $this->assertSame('users/ada.jpg', $employee->image);
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

    public function test_a_client_image_is_stored_and_recorded(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->post('/v1/admin/clients', [
                'name' => 'Globex',
                'code' => 'GLX',
                'email' => 'globex@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'phone' => '01700000000',
                'is_active' => 1,
                'image' => UploadedFile::fake()->image('globex.png'),
            ])
            ->assertCreated();

        $client = Client::firstWhere('email', 'globex@example.com');

        $this->assertNotNull($client->image, 'The image path was not saved.');
        $this->assertStringStartsWith('uploads/images/', $client->image);
        Storage::disk('public')->assertExists($client->image);
        Storage::disk('public')->assertExists(Asset::buildThumbnailPath($client->image));
    }

    /** Replacing a photo takes its generated thumbnail with it. */
    public function test_replacing_an_image_deletes_the_old_thumbnail(): void
    {
        Storage::fake('public');

        $client = Client::create([
            'name' => 'Globex', 'code' => 'GLX',
            'email' => 'globex@example.com', 'phone' => '01700000000',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        $this->actingAs(User::factory()->create())
            ->post("/v1/admin/clients/{$client->id}", [
                '_method' => 'put',
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'is_active' => 1,
                'image' => UploadedFile::fake()->image('first.png'),
            ])
            ->assertOk();

        $first = $client->fresh()->image;

        $this->actingAs(User::factory()->create())
            ->post("/v1/admin/clients/{$client->id}", [
                '_method' => 'put',
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'is_active' => 1,
                'image' => UploadedFile::fake()->image('second.png'),
            ])
            ->assertOk();

        $second = $client->fresh()->image;

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertMissing(Asset::buildThumbnailPath($first));
        Storage::disk('public')->assertExists(Asset::buildThumbnailPath($second));
    }

    /** The edit form reads the image back off the show endpoint. */
    public function test_the_show_endpoint_returns_the_image_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('clients/example.png', 'fake-bytes');

        $client = Client::create([
            'name' => 'Globex', 'code' => 'GLX',
            'email' => 'globex@example.com', 'phone' => '01700000000',
            'is_active' => true, 'image' => 'clients/example.png',
            'password' => 'secret123',
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/clients/{$client->id}")
            ->assertOk()
            ->assertJsonPath('result.image_url', Storage::disk('public')->url('clients/example.png'));
    }
}
