<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiQuestionCrudTest extends TestCase
{
    use RefreshDatabase;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

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
            'type' => 'mcq',
            'subject_id' => $this->subject->id,
            'exam_stage' => 'mcq',
            'exam_year' => 2023,
            'question_bn' => 'দণ্ডবিধির কত ধারায় চুরির শাস্তি বর্ণিত?',
            'options' => [
                ['option_bn' => 'ধারা ৩৭৮', 'is_correct' => false],
                ['option_bn' => 'ধারা ৩৭৯', 'is_correct' => true],
                ['option_bn' => 'ধারা ৩৮০', 'is_correct' => false],
                ['option_bn' => 'ধারা ৩৮১', 'is_correct' => false],
            ],
        ], $overrides);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/v1/admin/questions')->assertUnauthorized();
    }

    public function test_an_mcq_is_created_as_a_draft_with_its_options(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/questions', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('result.status', 'draft')
            ->assertJsonCount(4, 'result.options');
    }

    public function test_an_mcq_requires_exactly_one_correct_option(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/questions', $this->validPayload([
                'options' => [
                    ['option_bn' => 'ক', 'is_correct' => true],
                    ['option_bn' => 'খ', 'is_correct' => true],
                ],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['options']);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/questions', $this->validPayload([
                'options' => [
                    ['option_bn' => 'ক'],
                    ['option_bn' => 'খ'],
                ],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['options']);
    }

    public function test_a_written_question_rejects_options(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/questions', $this->validPayload([
                'type' => 'written',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['options']);
    }

    public function test_a_written_question_is_created_without_options(): void
    {
        $payload = $this->validPayload(['type' => 'written']);
        unset($payload['options']);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/questions', $payload)
            ->assertCreated()
            ->assertJsonPath('result.type', 'written');
    }

    public function test_updating_syncs_options(): void
    {
        $admin = User::factory()->create();

        $created = $this->actingAs($admin)
            ->postJson('/v1/admin/questions', $this->validPayload())
            ->assertCreated()
            ->json('result');

        $kept = $created['options'][0];

        $updated = $this->actingAs($admin)
            ->putJson("/v1/admin/questions/{$created['id']}", $this->validPayload([
                'options' => [
                    ['id' => $kept['id'], 'option_bn' => 'সংশোধিত ধারা', 'is_correct' => false],
                    ['option_bn' => 'নতুন অপশন', 'is_correct' => true],
                ],
            ]))
            ->assertOk()
            ->json('result');

        $this->assertCount(2, $updated['options']);
        $this->assertSame('সংশোধিত ধারা', $updated['options'][0]['option_bn']);
        $this->assertDatabaseCount('question_options', 2);
    }

    public function test_publish_requires_one_correct_option_and_unpublish_reverts(): void
    {
        $admin = User::factory()->create();

        $id = $this->actingAs($admin)
            ->postJson('/v1/admin/questions', $this->validPayload())
            ->json('result.id');

        $this->actingAs($admin)
            ->patchJson("/v1/admin/questions/{$id}/publish")
            ->assertOk()
            ->assertJsonPath('result.status', 'published');

        $this->actingAs($admin)
            ->patchJson("/v1/admin/questions/{$id}/unpublish", ['status' => 'archived'])
            ->assertOk()
            ->assertJsonPath('result.status', 'archived');

        Question::whereKey($id)->first()->options()->update(['is_correct' => false]);

        $this->actingAs($admin)
            ->patchJson("/v1/admin/questions/{$id}/publish")
            ->assertStatus(422);
    }

    public function test_index_filters_by_type_and_status_and_reports_counts(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->postJson('/v1/admin/questions', $this->validPayload());

        $written = $this->validPayload(['type' => 'written', 'question_bn' => 'চুরির উপাদানগুলো আলোচনা করুন।']);
        unset($written['options']);
        $this->actingAs($admin)->postJson('/v1/admin/questions', $written);

        $this->actingAs($admin)
            ->getJson('/v1/admin/questions?type=mcq')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.status_count.draft', 2);

        $this->actingAs($admin)
            ->getJson('/v1/admin/questions?status=published')
            ->assertOk()
            ->assertJsonCount(0, 'result.data');
    }

    public function test_soft_delete_removes_the_question_from_listings(): void
    {
        $admin = User::factory()->create();

        $id = $this->actingAs($admin)
            ->postJson('/v1/admin/questions', $this->validPayload())
            ->json('result.id');

        $this->actingAs($admin)
            ->deleteJson("/v1/admin/questions/{$id}")
            ->assertOk();

        $this->assertSoftDeleted('questions', ['id' => $id]);

        $this->actingAs($admin)
            ->getJson('/v1/admin/questions')
            ->assertJsonCount(0, 'result.data');
    }

    public function test_staff_role_cannot_publish_or_delete_questions(): void
    {
        $this->seed(UserSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(UserSeeder::STAFF);

        $id = Question::create([
            'type' => 'mcq', 'subject_id' => $this->subject->id,
            'question_bn' => 'প্রশ্ন', 'status' => 'draft',
        ])->id;

        $this->actingAs($staff)
            ->patchJson("/v1/admin/questions/{$id}/publish")
            ->assertForbidden();

        $this->actingAs($staff)
            ->deleteJson("/v1/admin/questions/{$id}")
            ->assertForbidden();

        $this->actingAs($staff)
            ->getJson('/v1/admin/questions')
            ->assertOk();
    }
}
