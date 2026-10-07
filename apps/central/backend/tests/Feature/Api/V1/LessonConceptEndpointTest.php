<?php

namespace Tests\Feature\Api\V1;

use App\Models\Concept;
use App\Models\ConceptPlacement;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\Domain;
use App\Models\EducationLevel;
use App\Models\Lesson;
use App\Models\LessonConcept;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LessonConceptEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_replace_lesson_concepts(): void
    {
        $moderator = User::factory()->create([
            'role' => 'moderator',
        ]);

        $curriculumSubject = $this->makeCurriculumSubject();
        $lesson = $this->makeLesson($curriculumSubject);

        $first = $this->makeConcept(
            $curriculumSubject,
            'Primul concept'
        );

        $second = $this->makeConcept(
            $curriculumSubject,
            'Al doilea concept'
        );

        Sanctum::actingAs($moderator);

        $this->putJson(
            "/api/v1/lessons/{$lesson->id}/concepts",
            [
                'concepts' => [
                    [
                        'concept_id' => $second->id,
                        'display_order' => 20,
                        'is_core' => false,
                    ],
                    [
                        'concept_id' => $first->id,
                        'display_order' => 10,
                        'is_core' => true,
                    ],
                ],
            ]
        )
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath(
                'data.0.concept.id',
                $first->id
            )
            ->assertJsonPath(
                'data.0.display_order',
                10
            )
            ->assertJsonPath(
                'data.0.is_core',
                true
            )
            ->assertJsonPath(
                'data.1.concept.id',
                $second->id
            );

        $this->assertDatabaseCount(
            'lesson_concepts',
            2
        );

        $this->assertDatabaseHas(
            'lesson_concepts',
            [
                'lesson_id' => $lesson->id,
                'concept_id' => $first->id,
                'display_order' => 10,
                'is_core' => 1,
            ]
        );
    }

    public function test_teacher_cannot_change_curricular_concepts(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $curriculumSubject = $this->makeCurriculumSubject();
        $lesson = $this->makeLesson($curriculumSubject);
        $concept = $this->makeConcept($curriculumSubject);

        Sanctum::actingAs($teacher);

        $this->putJson(
            "/api/v1/lessons/{$lesson->id}/concepts",
            [
                'concepts' => [
                    [
                        'concept_id' => $concept->id,
                        'display_order' => 1,
                        'is_core' => true,
                    ],
                ],
            ]
        )->assertForbidden();

        $this->assertDatabaseCount(
            'lesson_concepts',
            0
        );
    }

    public function test_out_of_scope_concept_is_rejected_without_losing_existing_links(): void
    {
        $moderator = User::factory()->create([
            'role' => 'moderator',
        ]);

        $curriculumSubject = $this->makeCurriculumSubject();
        $otherCurriculumSubject =
            $this->makeCurriculumSubject();

        $lesson = $this->makeLesson($curriculumSubject);

        $existing = $this->makeConcept(
            $curriculumSubject,
            'Concept existent'
        );

        $foreign = $this->makeConcept(
            $otherCurriculumSubject,
            'Concept străin'
        );

        LessonConcept::create([
            'lesson_id' => $lesson->id,
            'concept_id' => $existing->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        Sanctum::actingAs($moderator);

        $this->putJson(
            "/api/v1/lessons/{$lesson->id}/concepts",
            [
                'concepts' => [
                    [
                        'concept_id' => $foreign->id,
                        'display_order' => 1,
                        'is_core' => true,
                    ],
                ],
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'error.code',
                'LESSON_CONCEPT_OUT_OF_SCOPE'
            );

        $this->assertDatabaseHas(
            'lesson_concepts',
            [
                'lesson_id' => $lesson->id,
                'concept_id' => $existing->id,
            ]
        );

        $this->assertDatabaseMissing(
            'lesson_concepts',
            [
                'lesson_id' => $lesson->id,
                'concept_id' => $foreign->id,
            ]
        );
    }

    public function test_admin_can_clear_all_lesson_concepts(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $curriculumSubject = $this->makeCurriculumSubject();
        $lesson = $this->makeLesson($curriculumSubject);
        $concept = $this->makeConcept($curriculumSubject);

        LessonConcept::create([
            'lesson_id' => $lesson->id,
            'concept_id' => $concept->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson(
            "/api/v1/lessons/{$lesson->id}/concepts",
            [
                'concepts' => [],
            ]
        )
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseCount(
            'lesson_concepts',
            0
        );
    }

    private function makeCurriculumSubject(): CurriculumSubject
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
            'version' => 'TEST-'.$suffix,
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
            'name' => 'Disciplină '.$suffix,
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

    private function makeLesson(
        CurriculumSubject $curriculumSubject
    ): Lesson {
        return Lesson::create([
            'curriculum_subject_id' =>
                $curriculumSubject->id,
            'code' => 'lesson-'.uniqid(),
            'title' => 'Lecție test',
            'display_order' => 1,
            'status' => 'active',
        ]);
    }

    private function makeConcept(
        CurriculumSubject $curriculumSubject,
        string $title = 'Concept test'
    ): Concept {
        $concept = Concept::create([
            'code' => 'concept-'.uniqid(),
            'title' => $title,
            'status' => 'active',
        ]);

        $domain = Domain::create([
            'curriculum_subject_id' =>
                $curriculumSubject->id,
            'title' => 'Domeniu '.uniqid(),
            'display_order' => 1,
        ]);

        ConceptPlacement::create([
            'concept_id' => $concept->id,
            'domain_id' => $domain->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        return $concept;
    }
}
