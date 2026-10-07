<?php

namespace Tests\Feature\Api\V1;

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

class LessonVersionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_lesson_version(): void
    {
        $lesson = $this->makeLesson();

        $this->postJson(
            "/api/v1/lessons/{$lesson->id}/versions",
            []
        )->assertUnauthorized();
    }

    public function test_learner_cannot_create_lesson_version(): void
    {
        $learner = User::factory()->create([
            'role' => 'learner',
        ]);

        $lesson = $this->makeLesson();

        Sanctum::actingAs($learner);

        $this->postJson(
            "/api/v1/lessons/{$lesson->id}/versions",
            [
                'summary' => 'Draft neautorizat',
            ]
        )->assertForbidden();

        $this->assertDatabaseCount('lesson_versions', 0);
    }

    public function test_teacher_cannot_update_another_teachers_draft(): void
    {
        $owner = User::factory()->create([
            'role' => 'teacher',
        ]);

        $otherTeacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $lesson = $this->makeLesson();

        $version = $this->makeVersion(
            $lesson,
            $owner,
            'draft'
        );

        Sanctum::actingAs($otherTeacher);

        $this->patchJson(
            "/api/v1/lesson-versions/{$version->id}",
            [
                'summary' => 'Modificare neautorizată',
            ]
        )->assertForbidden();

        $this->assertDatabaseMissing('lesson_versions', [
            'id' => $version->id,
            'summary' => 'Modificare neautorizată',
        ]);
    }

    public function test_teacher_cannot_approve_submitted_version(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $lesson = $this->makeLesson();

        $version = $this->makeVersion(
            $lesson,
            $teacher,
            'submitted'
        );

        Sanctum::actingAs($teacher);

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/approve"
        )->assertForbidden();

        $this->assertDatabaseHas('lesson_versions', [
            'id' => $version->id,
            'status' => 'submitted',
        ]);
    }

    public function test_moderator_can_approve_submitted_version(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $moderator = User::factory()->create([
            'role' => 'moderator',
        ]);

        $lesson = $this->makeLesson();

        $version = $this->makeVersion(
            $lesson,
            $teacher,
            'submitted'
        );

        for ($i = 0; $i < 3; $i++) {
            $reviewer = User::factory()->create([
                'role' => 'teacher',
            ]);

            Sanctum::actingAs($reviewer);

            $this->postJson(
                "/api/v1/lesson-versions/{$version->id}/reviews",
                [
                    'verdict' => 'approve',
                    'correctness_score' => 5,
                    'curriculum_alignment_score' => 5,
                    'clarity_score' => 5,
                    'pedagogical_value_score' => 5,
                    'difficulty_fit_score' => 5,
                ]
            )->assertCreated();
        }

        Sanctum::actingAs($moderator);

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/approve"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'approved'
            );

        $this->assertDatabaseHas('lesson_versions', [
            'id' => $version->id,
            'status' => 'approved',
            'reviewed_by' => $moderator->id,
        ]);
    }

    public function test_admin_bypasses_lesson_version_policy(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $lesson = $this->makeLesson();

        $version = $this->makeVersion(
            $lesson,
            $teacher,
            'draft'
        );

        Sanctum::actingAs($admin);

        $this->patchJson(
            "/api/v1/lesson-versions/{$version->id}",
            [
                'summary' => 'Actualizat de administrator',
            ]
        )->assertOk();

        $this->assertDatabaseHas('lesson_versions', [
            'id' => $version->id,
            'summary' => 'Actualizat de administrator',
        ]);
    }

    private function makeVersion(
        Lesson $lesson,
        User $creator,
        string $status
    ): LessonVersion {
        return LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 1,
            'summary' => 'Versiune editorială',
            'content' => 'Conținut',
            'language_code' => 'ro',
            'status' => $status,
            'review_round' =>
                $status === 'submitted'
                    ? 1
                    : 0,
            'created_by' => $creator->id,
            'submitted_at' =>
                $status === 'submitted'
                    ? now()
                    : null,
        ]);
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
