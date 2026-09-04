<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The dashboard page is a shell — every number on it comes from
 * /v1/admin/dashboard/report. If this drifts, the page renders empty.
 */
class ApiDashboardReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/v1/admin/dashboard/report')->assertUnauthorized();
    }

    public function test_the_dashboard_page_is_a_shell_with_no_report_prop(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/dashboard/page')
                ->missing('report'));
    }

    public function test_it_returns_the_admin_report_the_page_renders(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'result' => [
                    'heading',
                    'cards' => [['label', 'value', 'icon', 'color']],
                    'range' => ['from', 'to', 'scoped', 'label'],
                ],
            ]);
    }

    public function test_the_stat_cards_carry_the_published_material_counts(): void
    {
        $program = Program::create([
            'slug' => 'llb-hons', 'name_bn' => 'অনার্স', 'name_en' => 'LLB (Hons)',
        ]);
        $subject = Subject::create([
            'program_id' => $program->id, 'slug' => 'jurisprudence',
            'name_bn' => 'আইনতত্ত্ব', 'name_en' => 'Jurisprudence',
        ]);

        StudyMaterial::create([
            'type' => 'suggestion', 'slug' => 's-1', 'title_bn' => 'সাজেশন',
            'subject_id' => $subject->id, 'status' => 'published', 'published_at' => now()->subMinute(),
        ]);
        StudyMaterial::create([
            'type' => 'book', 'slug' => 'b-draft', 'title_bn' => 'খসড়া বই',
            'subject_id' => $subject->id,
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk();

        $cards = collect($response->json('result.cards'))->pluck('value', 'label');

        $this->assertSame('1', $cards['Suggestions']);
        $this->assertSame('0', $cards['Books']);
        $this->assertSame('1', $cards['Subjects']);
    }

    /** The range picker has to actually narrow the count. */
    public function test_a_range_that_excludes_everything_zeroes_the_count(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/dashboard/report?date_from=2020-01-01&date_to=2020-01-31')
            ->assertOk();

        $cards = collect($response->json('result.cards'))->pluck('value', 'label');

        $this->assertSame('0', $cards['Suggestions']);
        $this->assertTrue($response->json('result.range.scoped'));
    }

    public function test_the_overview_needs_the_dashboard_permission(): void
    {
        $role = Role::findOrCreate('reader', 'web');
        $role->syncPermissions([Permission::findOrCreate('view roles', 'web')]);

        $reader = User::factory()->create();
        $reader->assignRole($role);

        $report = $this->actingAs($reader)
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk();

        $this->assertSame([], $report->json('result.cards'));
        $this->assertSame('My work', $report->json('result.heading'));

        $overview = $this->actingAs($reader)
            ->getJson('/v1/admin/dashboard')
            ->assertOk();

        $this->assertNull($overview->json('result.sections.overview'));
    }

    public function test_the_dashboard_permission_alone_unlocks_the_overview(): void
    {
        $role = Role::findOrCreate('viewer', 'web');
        $role->syncPermissions([Permission::findOrCreate('view dashboard', 'web')]);

        $viewer = User::factory()->create();
        $viewer->assignRole($role);

        $this->actingAs($viewer)
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk()
            ->assertJsonPath('result.heading', 'Dashboard Overview');

        $this->assertSame(
            1,
            $this->actingAs($viewer)
                ->getJson('/v1/admin/dashboard')
                ->assertOk()
                ->json('result.sections.overview.total_users'),
        );
    }

    public function test_a_user_without_the_dashboard_permission_gets_nothing(): void
    {
        $outsider = User::factory()->create();
        $outsider->assignRole(Role::findOrCreate('nobody', 'web'));

        $this->actingAs($outsider)
            ->getJson('/v1/admin/dashboard/report')
            ->assertOk()
            ->assertJsonPath('result.heading', 'My work')
            ->assertJsonPath('result.cards', []);
    }
}
