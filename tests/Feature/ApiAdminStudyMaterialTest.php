<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiAdminStudyMaterialTest extends TestCase
{
    use RefreshDatabase;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $program = Program::create([
            'slug' => 'bar-council', 'name_bn' => 'বার কাউন্সিল', 'name_en' => 'Bar Council',
            'has_levels' => false, 'has_exam_stages' => true, 'has_sessions' => false,
        ]);

        $this->subject = Subject::create([
            'program_id' => $program->id,
            'slug' => 'penal-code',
            'name_bn' => 'দণ্ডবিধি',
            'name_en' => 'Penal Code',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'suggestion',
            'title_bn' => 'দণ্ডবিধি সাজেশন',
            'subject_id' => $this->subject->id,
            'content_language' => 'bn',
            'exam_stage' => 'written',
            'exam_year' => 2026,
            'files' => [
                ['file' => UploadedFile::fake()->create('suggestion.pdf', 500, 'application/pdf')],
            ],
        ], $overrides);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/v1/admin/study-materials')->assertUnauthorized();
    }

    public function test_a_material_is_created_as_a_draft_with_its_files(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/study-materials', $this->validPayload([
                'files' => [
                    [
                        'file' => UploadedFile::fake()->create('part-1.pdf', 500, 'application/pdf'),
                        'label_bn' => '১ম খণ্ড',
                    ],
                    [
                        'file' => UploadedFile::fake()->create('part-2.pdf', 700, 'application/pdf'),
                        'label_bn' => '২য় খণ্ড',
                    ],
                ],
            ]));

        $response->assertCreated()
            ->assertJsonPath('result.status', 'draft')
            ->assertJsonCount(2, 'result.files');

        $material = StudyMaterial::first();
        $this->assertSame(2, $material->files()->count());

        foreach ($material->files as $file) {
            Storage::disk('local')->assertExists($file->path);
            $this->assertStringStartsWith('uploads/materials/', $file->path);
        }
    }

    public function test_the_pdf_lands_on_the_material_disk_not_the_default_disk(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/study-materials', $this->validPayload())
            ->assertCreated();

        $path = StudyMaterial::first()->files()->first()->path;

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertStringNotContainsString('applications', $path);
    }

    public function test_a_non_pdf_upload_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/study-materials', $this->validPayload([
                'files' => [
                    ['file' => UploadedFile::fake()->create('notes.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')],
                ],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['files.0.file']);
    }

    public function test_a_material_without_files_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/study-materials', $this->validPayload(['files' => []]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['files']);
    }

    public function test_bilingual_titles_fall_back_but_optional_fields_stay_null(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/study-materials', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('result.title.bn', 'দণ্ডবিধি সাজেশন')
            ->assertJsonPath('result.title.en', 'দণ্ডবিধি সাজেশন')
            ->assertJsonPath('result.title_en', null);
    }

    public function test_no_storage_path_appears_anywhere_in_the_payload(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/v1/admin/study-materials', $this->validPayload())
            ->assertCreated();

        $material = StudyMaterial::first();

        $payload = $this->actingAs($user)
            ->getJson("/v1/admin/study-materials/{$material->id}")
            ->assertOk()
            ->json();

        $this->assertStringNotContainsString('uploads/materials', json_encode($payload));
    }

    public function test_publishing_requires_at_least_one_file(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/v1/admin/study-materials', $this->validPayload())
            ->assertCreated();

        $material = StudyMaterial::first();
        $file = $material->files()->first();

        $this->actingAs($user)
            ->deleteJson("/v1/admin/study-materials/{$material->id}/files/{$file->id}")
            ->assertOk();

        $this->actingAs($user)
            ->patchJson("/v1/admin/study-materials/{$material->id}/publish")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_publish_sets_status_and_timestamp(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/v1/admin/study-materials', $this->validPayload())
            ->assertCreated();

        $material = StudyMaterial::first();

        $this->actingAs($user)
            ->patchJson("/v1/admin/study-materials/{$material->id}/publish")
            ->assertOk()
            ->assertJsonPath('result.status', 'published');

        $this->assertNotNull($material->refresh()->published_at);
    }

    /**
     * Staff are content authors: they may create and edit but never publish or
     * delete. The role must be assigned explicitly because TestCase::actingAs
     * promotes a role-less user to super-admin.
     */
    public function test_staff_can_create_but_cannot_publish_or_delete(): void
    {
        $this->seed(UserSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(UserSeeder::STAFF);

        $this->actingAs($staff)
            ->postJson('/v1/admin/study-materials', $this->validPayload())
            ->assertCreated();

        $material = StudyMaterial::first();

        $this->actingAs($staff)
            ->patchJson("/v1/admin/study-materials/{$material->id}/publish")
            ->assertForbidden();

        $this->actingAs($staff)
            ->deleteJson("/v1/admin/study-materials/{$material->id}")
            ->assertForbidden();
    }

    public function test_the_index_carries_status_counts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/v1/admin/study-materials', $this->validPayload())
            ->assertCreated();

        $this->actingAs($user)
            ->getJson('/v1/admin/study-materials')
            ->assertOk()
            ->assertJsonPath('result.status_count.draft', 1)
            ->assertJsonPath('result.status_count.published', 0);
    }

    public function test_a_file_from_another_material_cannot_be_deleted_through_this_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/v1/admin/study-materials', $this->validPayload())
            ->assertCreated();

        $this->actingAs($user)
            ->postJson('/v1/admin/study-materials', $this->validPayload([
                'title_bn' => 'আরেকটি সাজেশন',
            ]))
            ->assertCreated();

        $materials = StudyMaterial::orderBy('id')->get();
        $foreignFile = $materials[1]->files()->first();

        $this->actingAs($user)
            ->deleteJson("/v1/admin/study-materials/{$materials[0]->id}/files/{$foreignFile->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('material_files', ['id' => $foreignFile->id]);
    }
}
