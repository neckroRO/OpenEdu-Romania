<?php

namespace Tests\Feature\Api\V1;

use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Lesson;
use App\Models\LessonVersion;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonDetailEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_latest_published_lesson_version(): void
    {
        $lesson = $this->makeLesson();

        LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 1,
            'summary' => 'Versiunea publicată 1',
            'content' => 'Conținut vechi',
            'language_code' => 'ro',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 2,
            'summary' => 'Versiunea publicată 2',
            'content' => 'Conținut nou',
            'language_code' => 'ro',
            'status' => 'published',
            'published_at' => now(),
        ]);

        LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 3,
            'summary' => 'Draft ascuns',
            'content' => 'Nu trebuie afișat',
            'language_code' => 'ro',
            'status' => 'draft',
        ]);

        $this->getJson(
            "/api/v1/lessons/{$lesson->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.version.version_number',
                2
            )
            ->assertJsonPath(
                'data.version.summary',
                'Versiunea publicată 2'
            )
            ->assertJsonPath(
                'data.version.content',
                'Conținut nou'
            );
    }

    public function test_it_hides_lesson_without_published_version(): void
    {
        $lesson = $this->makeLesson();

        LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 1,
            'summary' => 'Doar draft',
            'language_code' => 'ro',
            'status' => 'draft',
        ]);

        $this->getJson(
            "/api/v1/lessons/{$lesson->id}"
        )->assertNotFound();
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

        $curriculumVersion = CurriculumVersion::create([
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
            'curriculum_version_id' =>
                $curriculumVersion->id,
            'education_level_id' => $level->id,
            'subject_id' => $subject->id,
            'display_order' => 1,
            'status' => 'active',
        ]);

        return Lesson::create([
            'curriculum_subject_id' =>
                $curriculumSubject->id,
            'code' => 'lesson-'.$suffix,
            'title' => 'Lecție test',
            'display_order' => 1,
            'status' => 'active',
        ]);
    }
}
