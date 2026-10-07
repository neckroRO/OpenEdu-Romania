<?php

namespace Tests\Feature\Api\V1;

use App\Models\Competency;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Lesson;
use App\Models\LessonCompetency;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumSubjectLessonEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_active_lessons_in_display_order(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $secondLesson = Lesson::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'code' => 'lesson-two',
            'title' => 'A doua lecție',
            'display_order' => 2,
            'status' => 'active',
        ]);

        $firstLesson = Lesson::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'code' => 'lesson-one',
            'title' => 'Prima lecție',
            'display_order' => 1,
            'status' => 'active',
        ]);

        Lesson::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'code' => 'inactive-lesson',
            'title' => 'Lecție inactivă',
            'display_order' => 0,
            'status' => 'inactive',
        ]);

        $response = $this->getJson(
            "/api/v1/curriculum-subjects/{$curriculumSubject->id}/lessons"
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $firstLesson->id)
            ->assertJsonPath('data.0.code', 'lesson-one')
            ->assertJsonPath('data.0.title', 'Prima lecție')
            ->assertJsonPath('data.1.id', $secondLesson->id);

        $requestId = $response->headers->get('X-Request-ID');

        $response->assertJsonPath(
            'meta.request_id',
            $requestId
        );
    }

    public function test_it_lists_only_active_competencies_for_a_lesson_in_link_order(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $lesson = Lesson::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'code' => 'linear-equations-basics',
            'title' => 'Ecuații de gradul I',
            'display_order' => 1,
            'status' => 'active',
        ]);

        $secondCompetency = $this->createCompetency(
            $curriculumSubject,
            '3.2',
            'A doua competență'
        );

        $firstCompetency = $this->createCompetency(
            $curriculumSubject,
            '1.2',
            'Prima competență'
        );

        $inactiveCompetency = $this->createCompetency(
            $curriculumSubject,
            '9.9',
            'Competență inactivă',
            'inactive'
        );

        LessonCompetency::create([
            'lesson_id' => $lesson->id,
            'competency_id' => $secondCompetency->id,
            'display_order' => 2,
            'is_core' => false,
        ]);

        LessonCompetency::create([
            'lesson_id' => $lesson->id,
            'competency_id' => $firstCompetency->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        LessonCompetency::create([
            'lesson_id' => $lesson->id,
            'competency_id' => $inactiveCompetency->id,
            'display_order' => 0,
            'is_core' => true,
        ]);

        $response = $this->getJson(
            "/api/v1/curriculum-subjects/{$curriculumSubject->id}/lessons"
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(2, 'data.0.competencies')
            ->assertJsonPath(
                'data.0.competencies.0.competency.id',
                $firstCompetency->id
            )
            ->assertJsonPath(
                'data.0.competencies.0.competency.code',
                '1.2'
            )
            ->assertJsonPath(
                'data.0.competencies.0.is_core',
                true
            )
            ->assertJsonPath(
                'data.0.competencies.1.competency.id',
                $secondCompetency->id
            )
            ->assertJsonPath(
                'data.0.competencies.1.is_core',
                false
            );
    }

    public function test_it_returns_an_empty_list_when_subject_has_no_lessons(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $response = $this->getJson(
            "/api/v1/curriculum-subjects/{$curriculumSubject->id}/lessons"
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonCount(0, 'data');
    }

    private function createCompetency(
        CurriculumSubject $curriculumSubject,
        string $code,
        string $title,
        string $status = 'active'
    ): Competency {
        return Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => null,
            'code' => $code,
            'type' => 'specific',
            'title' => $title,
            'display_order' => 1,
            'status' => $status,
        ]);
    }

    private function createCurriculumSubject(): CurriculumSubject
    {
        $curriculum = Curriculum::create([
            'code' => 'RO-TEST',
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
            'code' => 'grade-test',
            'name' => 'Clasă de test',
            'ordinal' => 1,
            'education_stage' => 'gimnazial',
        ]);

        $subject = Subject::create([
            'code' => 'test-subject',
            'name' => 'Disciplină de test',
            'status' => 'active',
        ]);

        return CurriculumSubject::create([
            'curriculum_version_id' => $version->id,
            'education_level_id' => $level->id,
            'subject_id' => $subject->id,
            'display_order' => 1,
            'status' => 'active',
        ]);
    }
}
