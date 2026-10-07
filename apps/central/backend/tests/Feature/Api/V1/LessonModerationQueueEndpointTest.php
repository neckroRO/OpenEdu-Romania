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

class LessonModerationQueueEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_sees_submitted_and_approved_versions(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $moderator = User::factory()->create([
            'role' => 'moderator',
        ]);

        $submittedLesson = $this->makeLesson();
        $approvedLesson = $this->makeLesson();
        $draftLesson = $this->makeLesson();

        $submitted = $this->makeVersion(
            $submittedLesson,
            $teacher,
            'submitted'
        );

        $approved = $this->makeVersion(
            $approvedLesson,
            $teacher,
            'approved'
        );

        $this->makeVersion(
            $draftLesson,
            $teacher,
            'draft'
        );

        Sanctum::actingAs($moderator);

        $this->getJson(
            '/api/v1/editor/lesson-moderation'
        )
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath(
                'data.0.id',
                $submitted->id
            )
            ->assertJsonPath(
                'data.0.status',
                'submitted'
            )
            ->assertJsonPath(
                'data.0.lesson.id',
                $submittedLesson->id
            )
            ->assertJsonPath(
                'data.0.creator.id',
                $teacher->id
            )
            ->assertJsonPath(
                'data.1.id',
                $approved->id
            )
            ->assertJsonPath(
                'data.1.status',
                'approved'
            );
    }

    public function test_teacher_cannot_view_lesson_moderation_queue(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        Sanctum::actingAs($teacher);

        $this->getJson(
            '/api/v1/editor/lesson-moderation'
        )->assertForbidden();
    }

    public function test_admin_can_view_lesson_moderation_queue(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson(
            '/api/v1/editor/lesson-moderation'
        )->assertOk();
    }

    private function makeVersion(
        Lesson $lesson,
        User $creator,
        string $status
    ): LessonVersion {
        return LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 1,
            'summary' => 'Versiune '.$status,
            'language_code' => 'ro',
            'status' => $status,
            'created_by' => $creator->id,
            'submitted_at' =>
                in_array(
                    $status,
                    ['submitted', 'approved'],
                    true
                )
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
            'title' => 'Lecție '.$suffix,
            'display_order' => 1,
            'status' => 'active',
        ]);
    }
}
