<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\Program;
use App\Models\StudyMaterial;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sitemap_lists_only_publicly_visible_urls(): void
    {
        $program = Program::create([
            'slug' => 'llb-hons', 'name_bn' => 'অনার্স', 'name_en' => 'LLB (Hons)',
        ]);

        $subject = Subject::create([
            'program_id' => $program->id, 'slug' => 'jurisprudence',
            'name_bn' => 'আইনতত্ত্ব', 'name_en' => 'Jurisprudence',
        ]);

        StudyMaterial::create([
            'type' => 'suggestion', 'slug' => 'published-suggestion',
            'title_bn' => 'প্রকাশিত', 'subject_id' => $subject->id,
            'status' => 'published', 'published_at' => now()->subMinute(),
        ]);

        StudyMaterial::create([
            'type' => 'suggestion', 'slug' => 'draft-suggestion',
            'title_bn' => 'খসড়া', 'subject_id' => $subject->id,
        ]);

        Notice::create([
            'slug' => 'published-notice', 'title_bn' => 'নোটিশ', 'body_bn' => 'বিস্তারিত',
            'status' => 'published', 'published_at' => now()->subMinute(),
        ]);

        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();

        $this->assertStringContainsString('/bn/materials/published-suggestion', $xml);
        $this->assertStringContainsString('/en/materials/published-suggestion', $xml);
        $this->assertStringContainsString('/bn/programs/llb-hons', $xml);
        $this->assertStringContainsString('/bn/subjects/jurisprudence', $xml);
        $this->assertStringContainsString('/bn/notices/published-notice', $xml);
        $this->assertStringNotContainsString('draft-suggestion', $xml);
    }

    public function test_robots_points_at_the_sitemap_and_blocks_the_admin(): void
    {
        $response = $this->get('/robots.txt')->assertOk();

        $body = $response->getContent();

        $this->assertStringContainsString('Disallow: /admin', $body);
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $body);
    }
}
