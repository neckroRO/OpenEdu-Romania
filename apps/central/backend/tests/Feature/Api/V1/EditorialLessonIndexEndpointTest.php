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

class EditorialLessonIndexEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_lists_only_lessons_with_own_versions(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $otherTeacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $ownLesson = $this->makeLesson();
        $otherLesson = $this->makeLesson();

        LessonVersion::create([
            'lesson_id' => $ownLesson->id,
            'version_number' => 1,
            'summary' => 'Versiunea 1',
            'language_code' => 'ro',
            'status' => 'published',
            'created_by' => $teacher->id,
            'published_at' => now(),
        ]);

        $latest = LessonVersion::create([
            'lesson_id' => $ownLesson->id,
            'version_number' => 2,
            'summary' => 'Versiunea 2',
            'language_code' => 'ro',
            'status' => 'draft',
            'created_by' => $teacher->id,
        ]);

        LessonVersion::create([
            'lesson_id' => $otherLesson->id,
            'version_number' => 1,
            'summary' => 'Alt profesor',
            'language_code' => 'ro',
            'status' => 'draft',
            'created_by' => $otherTeacher->id,
        ]);

        Sanctum::actingAs($teacher);

        $this->getJson('/api/v1/editor/lessons')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $ownLesson->id
            )
            ->assertJsonPath(
                'data.0.version.id',
                $latest->id
            )
            ->assertJsonPath(
                'data.0.version.version_number',
                2
            )
            ->assertJsonPath(
                'data.0.version.status',
                'draft'
            );
    }

    public function test_learner_cannot_list_editorial_lessons(): void
    {
        $learner = User::factory()->create([
            'role' => 'learner',
        ]);

        Sanctum::actingAs($learner);

        $this->getJson('/api/v1/editor/lessons')
            ->assertForbidden();
    }

    public function test_guest_cannot_list_editorial_lessons(): void
    {
        $this->getJson('/api/v1/editor/lessons')
            ->assertUnauthorized();
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
