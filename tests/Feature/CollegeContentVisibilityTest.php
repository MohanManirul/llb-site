<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\NoticeCategory;
use App\Models\ClassNote;
use App\Models\ClassRoutine;
use App\Models\College;
use App\Models\Notice;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollegeContentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function publishedNotice(?College $college, string $title): Notice
    {
        return Notice::create([
            'slug' => str($title)->slug()->toString(),
            'title_bn' => $title,
            'body_bn' => 'Body',
            'category' => NoticeCategory::General,
            'college_id' => $college?->id,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }

    private function publishedRoutine(College $college, string $title): ClassRoutine
    {
        return ClassRoutine::create([
            'college_id' => $college->id,
            'title_bn' => $title,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }

    private function publishedNote(College $college, string $title): ClassNote
    {
        return ClassNote::create([
            'college_id' => $college->id,
            'title_bn' => $title,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }

    public function test_a_student_only_sees_content_from_their_own_college(): void
    {
        $mine = College::factory()->create();
        $other = College::factory()->create();

        $this->publishedNotice($mine, 'Mine notice');
        $this->publishedNotice($other, 'Other notice');
        $this->publishedRoutine($mine, 'Mine routine');
        $this->publishedRoutine($other, 'Other routine');
        $this->publishedNote($mine, 'Mine note');
        $this->publishedNote($other, 'Other note');

        $student = Student::factory()->create(['college_id' => $mine->id]);

        $this->actingAs($student, 'student')
            ->getJson('/v1/student/college/notices')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.title.bn', 'Mine notice');

        $this->actingAs($student, 'student')
            ->getJson('/v1/student/college/routines')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.title.bn', 'Mine routine');

        $this->actingAs($student, 'student')
            ->getJson('/v1/student/college/notes')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.title.bn', 'Mine note');
    }

    public function test_reading_a_record_of_another_college_directly_is_a_404(): void
    {
        $mine = College::factory()->create();
        $other = College::factory()->create();

        $foreignNotice = $this->publishedNotice($other, 'Other notice');
        $foreignRoutine = $this->publishedRoutine($other, 'Other routine');
        $foreignNote = $this->publishedNote($other, 'Other note');

        $student = Student::factory()->create(['college_id' => $mine->id]);

        $this->actingAs($student, 'student')
            ->getJson("/v1/student/college/notices/{$foreignNotice->id}")
            ->assertNotFound();

        $this->actingAs($student, 'student')
            ->getJson("/v1/student/college/routines/{$foreignRoutine->id}")
            ->assertNotFound();

        $this->actingAs($student, 'student')
            ->getJson("/v1/student/college/notes/{$foreignNote->id}")
            ->assertNotFound();
    }

    public function test_a_student_without_a_college_sees_nothing(): void
    {
        $college = College::factory()->create();
        $this->publishedNotice($college, 'Some notice');
        $this->publishedRoutine($college, 'Some routine');

        $student = Student::factory()->create(['college_id' => null]);

        $this->actingAs($student, 'student')
            ->getJson('/v1/student/college/notices')
            ->assertOk()
            ->assertJsonCount(0, 'result.data');

        $this->actingAs($student, 'student')
            ->getJson('/v1/student/college/routines')
            ->assertOk()
            ->assertJsonCount(0, 'result.data');
    }

    public function test_draft_college_content_is_hidden_from_students(): void
    {
        $college = College::factory()->create();

        ClassRoutine::create([
            'college_id' => $college->id,
            'title_bn' => 'Draft routine',
            'status' => ContentStatus::Draft,
        ]);

        $student = Student::factory()->create(['college_id' => $college->id]);

        $this->actingAs($student, 'student')
            ->getJson('/v1/student/college/routines')
            ->assertOk()
            ->assertJsonCount(0, 'result.data');
    }

    public function test_college_notices_never_leak_into_the_public_notice_feed(): void
    {
        $college = College::factory()->create();

        $this->publishedNotice(null, 'Public notice');
        $this->publishedNotice($college, 'Private college notice');

        $response = $this->getJson('/v1/public/notices')->assertOk();

        $titles = collect($response->json('result.data'))->pluck('title.bn');

        $this->assertTrue($titles->contains('Public notice'));
        $this->assertFalse($titles->contains('Private college notice'));
    }

    public function test_a_college_notice_is_not_readable_through_the_public_show_route(): void
    {
        $college = College::factory()->create();
        $private = $this->publishedNotice($college, 'Private college notice');
        $public = $this->publishedNotice(null, 'Public notice');

        $this->getJson("/v1/public/notices/{$private->slug}")->assertNotFound();
        $this->getJson("/v1/public/notices/{$public->slug}")->assertOk();
    }

    public function test_a_teacher_can_only_manage_content_of_their_own_college(): void
    {
        $mine = College::factory()->create();
        $other = College::factory()->create();

        $teacher = Teacher::factory()->active()->create(['college_id' => $mine->id]);
        $foreignRoutine = $this->publishedRoutine($other, 'Other routine');

        $this->actingAs($teacher, 'teacher')
            ->getJson("/v1/teacher/college/routines/{$foreignRoutine->id}")
            ->assertNotFound();

        $this->actingAs($teacher, 'teacher')
            ->deleteJson("/v1/teacher/college/routines/{$foreignRoutine->id}")
            ->assertNotFound();
    }

    public function test_a_routine_created_by_a_teacher_is_pinned_to_their_college(): void
    {
        $college = College::factory()->create();
        $teacher = Teacher::factory()->active()->create(['college_id' => $college->id]);

        $id = $this->actingAs($teacher, 'teacher')
            ->postJson('/v1/teacher/college/routines', [
                'title_bn' => 'Spring routine',
                'title_en' => 'Spring routine',
            ])
            ->assertCreated()
            ->json('result.id');

        $this->assertDatabaseHas('class_routines', [
            'id' => $id,
            'college_id' => $college->id,
            'teacher_id' => $teacher->id,
            'status' => ContentStatus::Draft->value,
        ]);
    }

    public function test_a_teacher_cannot_spoof_the_college_on_create(): void
    {
        $mine = College::factory()->create();
        $other = College::factory()->create();

        $teacher = Teacher::factory()->active()->create(['college_id' => $mine->id]);

        $id = $this->actingAs($teacher, 'teacher')
            ->postJson('/v1/teacher/college/routines', [
                'title_bn' => 'Sneaky routine',
                'college_id' => $other->id,
                'teacher_id' => 9999,
            ])
            ->assertCreated()
            ->json('result.id');

        $this->assertDatabaseHas('class_routines', [
            'id' => $id,
            'college_id' => $mine->id,
            'teacher_id' => $teacher->id,
        ]);
    }

    public function test_publishing_a_routine_makes_it_visible_to_students_of_that_college(): void
    {
        $college = College::factory()->create();
        $teacher = Teacher::factory()->active()->create(['college_id' => $college->id]);
        $student = Student::factory()->create(['college_id' => $college->id]);

        $id = $this->actingAs($teacher, 'teacher')
            ->postJson('/v1/teacher/college/routines', ['title_bn' => 'Fresh routine'])
            ->assertCreated()
            ->json('result.id');

        $this->actingAs($student, 'student')
            ->getJson('/v1/student/college/routines')
            ->assertOk()
            ->assertJsonCount(0, 'result.data');

        $this->actingAs($teacher, 'teacher')
            ->patchJson("/v1/teacher/college/routines/{$id}/publish")
            ->assertOk();

        $this->actingAs($student, 'student')
            ->getJson('/v1/student/college/routines')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.title.bn', 'Fresh routine');
    }

    public function test_a_pending_teacher_cannot_reach_the_content_api(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher, 'teacher')
            ->getJson('/v1/teacher/college/routines')
            ->assertForbidden();
    }
}
