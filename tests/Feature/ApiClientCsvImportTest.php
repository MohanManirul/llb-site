<?php

namespace Tests\Feature;

use App\Jobs\ImportClientsChunkJob;
use App\Jobs\ImportClientsJob;
use App\Models\Client;
use App\Models\User;
use App\Services\Client\ClientImportService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiClientCsvImportTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function csv(string $body): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('clients.csv', $body);
    }

    private function upload(string $body)
    {
        return $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/clients/import', ['file' => $this->csv($body)]);
    }

    public function test_the_sample_file_the_modal_offers_actually_imports(): void
    {
        $path = public_path('samples/clients-import-sample.csv');

        $this->assertFileExists($path, 'The download link in ClientCsvUploadModal points at this file.');

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/clients/import', [
                'file' => new UploadedFile($path, 'clients-import-sample.csv', 'text/csv', null, true),
            ])
            ->assertOk()
            ->assertJsonPath('result.rows', 5);

        $this->assertSame(5, Client::count());

        $this->assertSame(
            ['8801611773390', '8801712345678', '8801722910456', '8801819337742', '8801915882203'],
            Client::query()->orderBy('phone')->pluck('phone')->all(),
        );
    }

    public function test_it_rejects_unauthenticated_uploads(): void
    {
        $this->postJson('/v1/admin/clients/import')->assertUnauthorized();
    }

    public function test_it_requires_a_csv_file(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/clients/import', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_it_validates_csv_file_format(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/clients/import', [
                'file' => UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_it_answers_with_the_queued_row_count_and_imports_them(): void
    {
        $this->upload(
            "Name,Email,Phone,Password\n".
            "Rakib,rakib@example.com,01711000001,SecurePass123\n".
            "Nadia,nadia@example.com,01711000002,SecurePass123\n"
        )
            ->assertOk()
            ->assertJsonPath('result.rows', 2);

        $this->assertSame(2, Client::count());
    }

    public function test_the_upload_is_handed_to_a_queued_job(): void
    {
        Queue::fake();

        $this->upload("Name,Email,Phone,Password\nRakib,rakib@example.com,01711000001,SecurePass123\n")
            ->assertOk();

        Queue::assertPushed(ImportClientsChunkJob::class, 1);
        $this->assertSame(0, Client::count());
    }

    public function test_the_chunk_job_fans_out_one_job_per_chunk(): void
    {
        $csv = "name,email,phone,password\n";
        for ($i = 1; $i <= 250; $i++) {
            $csv .= "User {$i},u{$i}@example.com,01711".str_pad((string) $i, 6, '0', STR_PAD_LEFT).",SecurePass123\n";
        }

        $batch = app(ClientImportService::class)->readRows($this->csv($csv));

        Queue::fake();
        (new ImportClientsChunkJob($batch['rows'], $batch['columns']))->handle();

        Queue::assertPushed(
            ImportClientsJob::class,
            (int) ceil(250 / ClientImportService::CHUNK_SIZE)
        );
    }

    public function test_the_import_job_ignores_an_empty_chunk(): void
    {
        (new ImportClientsJob([], []))->handle(app(ClientImportService::class));

        $this->assertSame(0, Client::count());
    }

    public function test_it_imports_without_storing_the_uploaded_file(): void
    {
        Storage::fake('local');

        $this->upload("Name,Email,Phone,Password\nRakib,rakib@example.com,01711000001,SecurePass123\n")
            ->assertOk();

        $this->assertSame(1, Client::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_duplicate_row_is_skipped_with_a_reason(): void
    {
        $this->upload(
            "Name,Email,Phone,Password\n".
            "Rakib,rakib@example.com,01711000001,SecurePass123\n".
            "Duplicate,rakib@example.com,01711000003,SecurePass123\n"
        )->assertOk();

        $this->assertSame(1, Client::count());
        $this->assertSame('Rakib', Client::sole()->name);
    }

    public function test_a_row_that_fails_validation_is_skipped_with_a_reason(): void
    {
        $this->upload(
            "Name,Email,Phone,Password\n".
            "Rakib,not-an-email,01711000001,SecurePass123\n".
            "Nadia,nadia@example.com,01711000002,short\n"
        )->assertOk();

        $this->assertSame(0, Client::count());
    }

    public function test_a_client_that_already_exists_is_skipped(): void
    {
        Client::create([
            'name' => 'Existing',
            'email' => 'rakib@example.com',
            'phone' => '01711000009',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        $this->upload(
            "Name,Email,Phone,Password\nRakib,rakib@example.com,01711000001,SecurePass123\n"
        )->assertOk();

        $this->assertSame(1, Client::count());
        $this->assertSame('Existing', Client::sole()->name);
    }

    public function test_a_bad_header_is_rejected_before_anything_is_imported(): void
    {
        $this->upload("Firstname,Contact\nRakib,01711000001\n")
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.file.0',
                'CSV is missing required columns: name, email, phone, password.'
            );

        $this->assertSame(0, Client::count());
    }

    public function test_a_header_missing_only_the_password_column_is_rejected(): void
    {
        $this->upload("Name,Email,Phone\nRakib,r@example.com,01711000001\n")
            ->assertStatus(422)
            ->assertJsonPath('errors.file.0', 'CSV is missing required columns: password.');

        $this->assertSame(0, Client::count());
    }

    public function test_an_empty_csv_is_rejected(): void
    {
        $this->upload('')
            ->assertStatus(422)
            ->assertJsonPath('errors.file.0', 'The CSV file is empty.');

        $this->assertSame(0, Client::count());
    }

    public function test_it_reads_aliased_headers_and_strips_a_byte_order_mark(): void
    {
        $this->upload(
            "\u{FEFF}Full Name,Email Address,Mobile,Password\n".
            "Rakib,rakib@example.com,01711000001,SecurePass123\n"
        )
            ->assertOk()
            ->assertJsonPath('result.rows', 1);

        $this->assertSame(1, Client::count());
    }

    public function test_the_import_needs_the_create_clients_permission(): void
    {
        $role = Role::findOrCreate('reader', 'web');
        $role->syncPermissions([Permission::findOrCreate('view clients', 'web')]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->postJson('/v1/admin/clients/import', [
                'file' => $this->csv("Name,Email,Phone,Password\nRakib,rakib@example.com,01711000001,SecurePass123\n"),
            ])
            ->assertForbidden();
    }
}
