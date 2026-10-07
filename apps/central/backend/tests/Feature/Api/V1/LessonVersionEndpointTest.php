<?php

namespace Tests\Feature\Api\V1;

use App\Enums\LessonVersionStatus;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Lesson;
use App\Models\LessonVersion;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LessonVersionEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_first_draft_version(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $lesson = $this->makeLesson();

        Sanctum::actingAs($teacher);

        $response = $this->postJson(
            "/api/v1/lessons/{$lesson->id}/versions",
            [
                'summary' => 'Introducere în ecuațiile de gradul I.',
                'learning_objectives' => [
                    'Identificarea unei ecuații.',
                    'Rezolvarea unei ecuații simple.',
                ],
                'content' => 'Conținutul lecției.',
                'estimated_duration_minutes' => 50,
                'language_code' => 'ro',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.lesson_id', $lesson->id)
            ->assertJsonPath('data.version_number', 1)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath(
                'data.estimated_duration_minutes',
                50
            )
            ->assertJsonPath(
                'data.learning_objectives.0',
                'Identificarea unei ecuații.'
            );

        $this->assertDatabaseHas('lesson_versions', [
            'lesson_id' => $lesson->id,
            'version_number' => 1,
            'status' => 'draft',
            'created_by' => $teacher->id,
        ]);
    }

    public function test_second_version_cannot_be_created_while_one_is_in_progress(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $lesson = $this->makeLesson();

        LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 1,
            'summary' => 'Draft existent',
            'language_code' => 'ro',
            'status' => LessonVersionStatus::Draft,
            'created_by' => $teacher->id,
        ]);

        Sanctum::actingAs($teacher);

        $response = $this->postJson(
            "/api/v1/lessons/{$lesson->id}/versions",
            [
                'summary' => 'Al doilea draft',
            ]
        );

        $response
            ->assertStatus(409)
            ->assertJsonPath(
                'error.code',
                'LESSON_VERSION_IN_PROGRESS'
            );

        $this->assertSame(
            1,
            LessonVersion::query()
                ->where('lesson_id', $lesson->id)
                ->count()
        );
    }

    public function test_teacher_can_update_own_draft(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $lesson = $this->makeLesson();

        $version = LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 1,
            'summary' => 'Rezumat inițial',
            'content' => 'Conținut inițial',
            'language_code' => 'ro',
            'status' => LessonVersionStatus::Draft,
            'created_by' => $teacher->id,
        ]);

        Sanctum::actingAs($teacher);

        $response = $this->patchJson(
            "/api/v1/lesson-versions/{$version->id}",
            [
                'summary' => 'Rezumat actualizat',
                'estimated_duration_minutes' => 45,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.summary',
                'Rezumat actualizat'
            )
            ->assertJsonPath(
                'data.estimated_duration_minutes',
                45
            );

        $this->assertDatabaseHas('lesson_versions', [
            'id' => $version->id,
            'summary' => 'Rezumat actualizat',
            'estimated_duration_minutes' => 45,
        ]);
    }

    public function test_non_draft_version_cannot_be_edited(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $lesson = $this->makeLesson();

        $version = LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 1,
            'summary' => 'Versiune trimisă',
            'language_code' => 'ro',
            'status' => LessonVersionStatus::Submitted,
            'created_by' => $teacher->id,
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($teacher);

        $response = $this->patchJson(
            "/api/v1/lesson-versions/{$version->id}",
            [
                'summary' => 'Modificare interzisă',
            ]
        );

        $response
            ->assertStatus(409)
            ->assertJsonPath(
                'error.code',
                'LESSON_VERSION_NOT_EDITABLE'
            );

        $this->assertDatabaseMissing('lesson_versions', [
            'id' => $version->id,
            'summary' => 'Modificare interzisă',
        ]);
    }

    public function test_version_can_follow_full_editorial_workflow(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $moderator = User::factory()->create([
            'role' => 'moderator',
        ]);

        $lesson = $this->makeLesson();

        $version = LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 1,
            'summary' => 'Lecție pentru workflow',
            'language_code' => 'ro',
            'status' => LessonVersionStatus::Draft,
            'created_by' => $teacher->id,
        ]);

        Sanctum::actingAs($teacher);

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/submit"
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'submitted');

        Sanctum::actingAs($moderator);

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/reject",
            [
                'review_note' => 'Clarifică obiectivele.',
            ]
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath(
                'data.review_note',
                'Clarifică obiectivele.'
            );

        Sanctum::actingAs($teacher);

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/revise"
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/submit"
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'submitted');

        Sanctum::actingAs($moderator);

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/approve"
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/publish"
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $version->refresh();

        $this->assertSame(
            LessonVersionStatus::Published,
            $version->status
        );

        $this->assertNotNull($version->published_at);
        $this->assertSame(
            $moderator->id,
            $version->reviewed_by
        );
    }

    public function test_new_draft_becomes_version_two_after_version_one_is_published(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $lesson = $this->makeLesson();

        LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 1,
            'summary' => 'Versiunea publicată',
            'language_code' => 'ro',
            'status' => LessonVersionStatus::Published,
            'created_by' => $teacher->id,
            'published_at' => now(),
        ]);

        Sanctum::actingAs($teacher);

        $response = $this->postJson(
            "/api/v1/lessons/{$lesson->id}/versions",
            [
                'summary' => 'Noua versiune',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.version_number', 2)
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('lesson_versions', [
            'lesson_id' => $lesson->id,
            'version_number' => 2,
            'status' => 'draft',
            'created_by' => $teacher->id,
        ]);

        $this->assertSame(
            2,
            LessonVersion::query()
                ->where('lesson_id', $lesson->id)
                ->count()
        );
    }

    private function makeLesson(): Lesson
    {
        $suffix = uniqid();

        $curriculum = Curriculum::create([
            'code' => 'RO-'.$suffix,
            'name' => 'Curriculum test',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $version = CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST',
            'status' => 'active',
        ]);

        $level = EducationLevel::create([
            'code' => 'grade-'.$suffix,
            'name' => 'Clasă test',
            'ordinal' => 7,
            'education_stage' => 'gimnazial',
        ]);

        $subject = Subject::create([
            'code' => 'subject-'.$suffix,
            'name' => 'Disciplină test',
            'status' => 'active',
        ]);

        $curriculumSubject = CurriculumSubject::create([
            'curriculum_version_id' => $version->id,
            'education_level_id' => $level->id,
            'subject_id' => $subject->id,
            'display_order' => 1,
            'status' => 'active',
        ]);

        return Lesson::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'code' => 'lesson-'.$suffix,
            'title' => 'Lecție de test',
            'display_order' => 1,
            'status' => 'active',
        ]);
    }
}
