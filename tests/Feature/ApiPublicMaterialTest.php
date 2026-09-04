<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiPublicMaterialTest extends TestCase
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

    private function createMaterial(array $overrides = [], bool $publish = true): StudyMaterial
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/v1/admin/study-materials', array_merge([
                'type' => 'suggestion',
                'title_bn' => 'দণ্ডবিধি সাজেশন',
                'subject_id' => $this->subject->id,
                'content_language' => 'bn',
                'files' => [
                    ['file' => UploadedFile::fake()->create('one.pdf', 400, 'application/pdf'), 'label_bn' => '১ম খণ্ড'],
                    ['file' => UploadedFile::fake()->create('two.pdf', 400, 'application/pdf'), 'label_bn' => '২য় খণ্ড'],
                ],
            ], $overrides))
            ->assertCreated();

        $material = StudyMaterial::orderByDesc('id')->first();

        if ($publish) {
            $this->actingAs($user)
                ->patchJson("/v1/admin/study-materials/{$material->id}/publish")
                ->assertOk();
        }

        // A fresh unauthenticated context for the public assertions.
        auth()->guard('web')->logout();

        return $material->refresh();
    }

    public function test_drafts_are_invisible_and_published_materials_are_visible(): void
    {
        $this->createMaterial(['title_bn' => 'খসড়া সাজেশন'], publish: false);
        $published = $this->createMaterial();

        $response = $this->getJson('/v1/public/materials')->assertOk();

        $this->assertSame(1, $response->json('result.meta.total'));
        $this->assertSame($published->slug, $response->json('result.data.0.slug'));
    }

    public function test_the_index_is_length_aware(): void
    {
        $this->createMaterial();

        $this->getJson('/v1/public/materials')
            ->assertOk()
            ->assertJsonPath('result.meta.total', 1)
            ->assertJsonStructure(['result' => ['meta' => ['total', 'last_page', 'current_page']]]);
    }

    public function test_a_draft_slug_404s_with_the_json_envelope(): void
    {
        $draft = $this->createMaterial(publish: false);

        $this->getJson("/v1/public/materials/{$draft->slug}")
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_show_increments_view_count_and_hides_paths(): void
    {
        $material = $this->createMaterial();

        $payload = $this->getJson("/v1/public/materials/{$material->slug}")
            ->assertOk()
            ->json();

        $this->assertSame(1, $material->refresh()->view_count);
        $this->assertStringNotContainsString('uploads/materials', json_encode($payload));
        $this->assertCount(2, $payload['result']['files']);
        $this->assertNotEmpty($payload['result']['files'][0]['download_url']);
    }

    public function test_search_finds_a_bangla_material_via_its_english_subject_name(): void
    {
        $this->createMaterial();

        $this->getJson('/v1/public/materials?search=Penal')
            ->assertOk()
            ->assertJsonPath('result.meta.total', 1);

        $this->getJson('/v1/public/materials?search='.urlencode('দণ্ডবিধি'))
            ->assertOk()
            ->assertJsonPath('result.meta.total', 1);
    }

    public function test_downloading_counts_on_both_the_file_and_the_material(): void
    {
        $material = $this->createMaterial();
        $file = $material->files()->orderBy('sort_order')->skip(1)->first();

        $url = "/v1/public/materials/{$material->slug}/files/{$file->id}/download";

        $this->get($url)->assertOk();
        $this->get($url)->assertOk();

        $this->assertSame(2, $file->refresh()->download_count);
        $this->assertSame(2, $material->refresh()->download_count);
        $this->assertSame(0, $material->files()->orderBy('sort_order')->first()->download_count);
    }

    public function test_the_download_filename_is_the_ascii_slug_with_a_part_suffix(): void
    {
        $material = $this->createMaterial();
        $file = $material->files()->orderBy('sort_order')->skip(1)->first();

        $response = $this->get("/v1/public/materials/{$material->slug}/files/{$file->id}/download");

        $disposition = (string) $response->headers->get('content-disposition');

        $this->assertStringContainsString("{$material->slug}-part-2.pdf", $disposition);
        $this->assertMatchesRegularExpression('/filename="?[\x20-\x7e]+"?/', $disposition);
    }

    public function test_a_file_id_from_another_material_404s_through_this_slug(): void
    {
        $first = $this->createMaterial();
        $second = $this->createMaterial(['title_bn' => 'অন্য সাজেশন']);

        $foreignFile = $second->files()->first();

        $this->getJson("/v1/public/materials/{$first->slug}/files/{$foreignFile->id}/download")
            ->assertNotFound();

        $this->assertSame(0, $foreignFile->refresh()->download_count);
    }

    public function test_a_draft_materials_file_cannot_be_downloaded(): void
    {
        $draft = $this->createMaterial(publish: false);
        $file = $draft->files()->first();

        $this->getJson("/v1/public/materials/{$draft->slug}/files/{$file->id}/download")
            ->assertNotFound();
    }

    public function test_a_missing_blob_404s_instead_of_500ing(): void
    {
        $material = $this->createMaterial();
        $file = $material->files()->first();

        Storage::disk($file->disk)->delete($file->path);

        $this->getJson("/v1/public/materials/{$material->slug}/files/{$file->id}/download")
            ->assertNotFound();
    }

    public function test_the_download_route_is_throttled(): void
    {
        $material = $this->createMaterial();
        $file = $material->files()->first();

        $url = "/v1/public/materials/{$material->slug}/files/{$file->id}/download";

        for ($i = 0; $i < 30; $i++) {
            $this->get($url)->assertOk();
        }

        $this->get($url)->assertStatus(429);
    }
}
