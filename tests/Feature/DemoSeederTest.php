<?php

namespace Tests\Feature;

use App\Models\MaterialDownload;
use App\Models\MaterialFile;
use App\Models\Notice;
use App\Models\StudyMaterial;
use App\Models\VisitorSession;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\DemoAnalyticsSeeder;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed(AcademicStructureSeeder::class);
        $this->seed(DemoContentSeeder::class);
    }

    public function test_it_seeds_materials_with_real_pdf_blobs(): void
    {
        $this->assertGreaterThan(0, StudyMaterial::count());
        $this->assertGreaterThan(0, MaterialFile::count());

        foreach (MaterialFile::all() as $file) {
            Storage::disk($file->disk)->assertExists($file->path);

            $bytes = Storage::disk($file->disk)->get($file->path);

            $this->assertStringStartsWith('%PDF-1.4', $bytes);
            $this->assertSame(strlen($bytes), $file->size);
            $this->assertSame(hash('sha256', $bytes), $file->checksum);
        }
    }

    public function test_it_seeds_every_content_state_and_notices(): void
    {
        $this->assertGreaterThan(0, StudyMaterial::where('status', 'published')->count());
        $this->assertGreaterThan(0, StudyMaterial::where('status', 'draft')->count());
        $this->assertGreaterThan(0, StudyMaterial::where('status', 'archived')->count());

        $this->assertGreaterThan(0, Notice::where('status', 'published')->count());
        $this->assertGreaterThan(0, Notice::where('is_pinned', true)->count());
        $this->assertGreaterThan(0, Notice::whereNotNull('attachment_path')->count());
        $this->assertGreaterThan(0, Notice::whereNotNull('expires_at')->count());
    }

    public function test_seeded_content_is_immediately_downloadable_through_the_public_api(): void
    {
        $material = StudyMaterial::query()->publiclyVisible()->with('files')->first();
        $file = $material->files->first();

        $this->get("/v1/public/materials/{$material->slug}/files/{$file->id}/download")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_reseeding_adds_nothing(): void
    {
        $counts = [StudyMaterial::count(), MaterialFile::count(), Notice::count()];

        $this->seed(DemoContentSeeder::class);

        $this->assertSame($counts, [StudyMaterial::count(), MaterialFile::count(), Notice::count()]);
    }

    public function test_analytics_counters_match_the_generated_events(): void
    {
        $this->seed(DemoAnalyticsSeeder::class);

        $this->assertGreaterThan(0, VisitorSession::count());
        $this->assertGreaterThan(0, MaterialDownload::count());

        foreach (MaterialFile::all() as $file) {
            $this->assertSame(
                MaterialDownload::where('material_file_id', $file->id)->count(),
                $file->download_count,
            );
        }

        foreach (StudyMaterial::query()->publiclyVisible()->get() as $material) {
            $this->assertSame(
                MaterialDownload::where('study_material_id', $material->id)->count(),
                $material->download_count,
            );
        }

        $draft = StudyMaterial::where('status', 'draft')->first();
        $this->assertSame(0, MaterialDownload::where('study_material_id', $draft->id)->count());
    }

    public function test_reseeding_analytics_adds_nothing(): void
    {
        $this->seed(DemoAnalyticsSeeder::class);

        $counts = [VisitorSession::count(), MaterialDownload::count()];

        $this->seed(DemoAnalyticsSeeder::class);

        $this->assertSame($counts, [VisitorSession::count(), MaterialDownload::count()]);
    }
}
