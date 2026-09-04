<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\StudyMaterial;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPageRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_redirects_to_the_default_locale(): void
    {
        $this->get('/')->assertRedirect('/bn');
    }

    public function test_the_root_honours_the_remembered_locale_cookie(): void
    {
        $this->withUnencryptedCookie('locale', 'en')
            ->get('/')
            ->assertRedirect('/en');
    }

    public function test_the_home_page_renders_in_both_locales(): void
    {
        $this->get('/bn')->assertOk()
            ->assertInertia(fn ($page) => $page->component('public/home/page'));

        $this->get('/en')->assertOk();
    }

    public function test_an_unknown_locale_prefix_404s(): void
    {
        $this->get('/fr')->assertNotFound();
    }

    public function test_visiting_a_locale_refreshes_the_locale_cookie(): void
    {
        $this->get('/en')->assertCookie('locale', 'en', false);
    }

    public function test_an_unknown_material_slug_is_a_real_404_with_the_public_error_page(): void
    {
        $this->get('/bn/materials/no-such-material')
            ->assertNotFound()
            ->assertInertia(fn ($page) => $page->component('public/errors/not-found/page'));
    }

    public function test_an_admin_404_still_gets_the_admin_error_page(): void
    {
        $this->get('/admin/no-such-page')
            ->assertNotFound()
            ->assertInertia(fn ($page) => $page->component('admin/errors/not-found/page'));
    }

    public function test_a_draft_material_page_is_a_404_not_a_soft_200(): void
    {
        $program = Program::create([
            'slug' => 'llb-hons', 'name_bn' => 'অনার্স', 'name_en' => 'LLB (Hons)',
        ]);

        $subject = Subject::create([
            'program_id' => $program->id, 'slug' => 'jurisprudence',
            'name_bn' => 'আইনতত্ত্ব', 'name_en' => 'Jurisprudence',
        ]);

        $draft = StudyMaterial::create([
            'type' => 'suggestion', 'slug' => 'draft-suggestion',
            'title_bn' => 'খসড়া', 'subject_id' => $subject->id,
        ]);

        $this->get("/bn/materials/{$draft->slug}")->assertNotFound();
    }

    public function test_a_published_material_page_renders_with_meta_props(): void
    {
        $program = Program::create([
            'slug' => 'llb-hons', 'name_bn' => 'অনার্স', 'name_en' => 'LLB (Hons)',
        ]);

        $subject = Subject::create([
            'program_id' => $program->id, 'slug' => 'jurisprudence',
            'name_bn' => 'আইনতত্ত্ব', 'name_en' => 'Jurisprudence',
        ]);

        $material = StudyMaterial::create([
            'type' => 'suggestion', 'slug' => 'jurisprudence-suggestion',
            'title_bn' => 'আইনতত্ত্ব সাজেশন', 'subject_id' => $subject->id,
            'status' => 'published', 'published_at' => now()->subMinute(),
        ]);

        $this->get("/bn/materials/{$material->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/materials/show/page')
                ->where('materialSlug', $material->slug)
                ->where('meta.title_bn', 'আইনতত্ত্ব সাজেশন'));
    }

    public function test_the_browse_pages_render_with_their_pinned_types(): void
    {
        $this->get('/bn/browse')->assertOk()
            ->assertInertia(fn ($page) => $page->where('pinnedType', null));

        $this->get('/bn/suggestions')->assertOk()
            ->assertInertia(fn ($page) => $page->where('pinnedType', 'suggestion'));

        $this->get('/en/books')->assertOk()
            ->assertInertia(fn ($page) => $page->where('pinnedType', 'book'));
    }

    public function test_public_pages_share_locale_and_programs(): void
    {
        Program::create([
            'slug' => 'llb-hons', 'name_bn' => 'অনার্স', 'name_en' => 'LLB (Hons)',
        ]);

        $this->get('/en/browse')
            ->assertInertia(fn ($page) => $page
                ->where('locale', 'en')
                ->has('programs', 1));
    }

    public function test_admin_pages_do_not_pay_for_the_program_list(): void
    {
        Program::create([
            'slug' => 'llb-hons', 'name_bn' => 'অনার্স', 'name_en' => 'LLB (Hons)',
        ]);

        $this->get('/admin/login')
            ->assertInertia(fn ($page) => $page->has('programs', 0));
    }
}
