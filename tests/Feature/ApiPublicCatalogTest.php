<?php

namespace Tests\Feature;

use Database\Seeders\AcademicStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AcademicStructureSeeder::class);
    }

    public function test_the_catalog_needs_no_authentication(): void
    {
        $this->getJson('/v1/public/programs')->assertOk();
        $this->getJson('/v1/public/sessions')->assertOk();
        $this->getJson('/v1/public/subjects')->assertOk();
        $this->getJson('/v1/public/filters')->assertOk();
    }

    public function test_no_session_cookie_is_set_on_the_public_api(): void
    {
        $response = $this->getJson('/v1/public/programs');

        $this->assertEmpty(
            array_filter(
                $response->headers->getCookies(),
                fn ($cookie) => str_contains($cookie->getName(), 'session'),
            ),
        );
    }

    public function test_a_program_carries_its_server_built_filters(): void
    {
        $hons = $this->getJson('/v1/public/programs/llb-hons')->assertOk()->json('result');
        $honsKeys = array_column($hons['filters'], 'key');

        $this->assertContains('level', $honsKeys);
        $this->assertContains('session', $honsKeys);
        $this->assertNotContains('exam_stage', $honsKeys);

        $bjs = $this->getJson('/v1/public/programs/bjs')->assertOk()->json('result');
        $bjsKeys = array_column($bjs['filters'], 'key');

        $this->assertNotContains('level', $bjsKeys);
        $this->assertNotContains('session', $bjsKeys);
        $this->assertContains('exam_stage', $bjsKeys);
        $this->assertContains('type', $bjsKeys);
    }

    public function test_bar_council_subjects_come_back_level_less(): void
    {
        $subjects = $this->getJson('/v1/public/subjects?program=bar-council')
            ->assertOk()
            ->json('result');

        $this->assertNotEmpty($subjects);

        foreach ($subjects as $subject) {
            $this->assertNull($subject['level']);
        }
    }

    public function test_subject_names_come_back_in_both_languages(): void
    {
        $subject = $this->getJson('/v1/public/subjects?program=bar-council')
            ->assertOk()
            ->json('result.0');

        $this->assertNotSame('', $subject['name']['bn']);
        $this->assertNotSame('', $subject['name']['en']);
        $this->assertNotSame($subject['name']['bn'], $subject['name']['en']);
    }

    public function test_an_unknown_program_slug_returns_the_json_not_found_envelope(): void
    {
        $this->getJson('/v1/public/programs/no-such-program')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Record not found.');
    }
}
