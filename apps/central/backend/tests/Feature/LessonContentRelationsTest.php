<?php

namespace Tests\Feature;

use App\Enums\LessonResourceRole;
use App\Models\Concept;
use App\Models\ConceptPlacement;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\Domain;
use App\Models\EducationLevel;
use App\Models\Lesson;
use App\Models\LessonConcept;
use App\Models\LessonVersion;
use App\Models\LessonVersionResource;
use App\Models\Resource;
use App\Models\Subject;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonContentRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_concepts_are_returned_in_display_order(): void
    {
        $curriculumSubject = $this->makeCurriculumSubject();
        $lesson = $this->makeLesson($curriculumSubject);

        $firstConcept = $this->makeConcept(
            $curriculumSubject,
            'Primul concept'
        );

        $secondConcept = $this->makeConcept(
            $curriculumSubject,
            'Al doilea concept'
        );

        LessonConcept::create([
            'lesson_id' => $lesson->id,
            'concept_id' => $firstConcept->id,
            'display_order' => 20,
            'is_core' => true,
        ]);

        LessonConcept::create([
            'lesson_id' => $lesson->id,
            'concept_id' => $secondConcept->id,
            'display_order' => 10,
            'is_core' => false,
        ]);

        $concepts = $lesson->concepts()->get();

        $this->assertSame(
            [
                $secondConcept->id,
                $firstConcept->id,
            ],
            $concepts->pluck('id')->all()
        );

        $this->assertTrue(
            $firstConcept
                ->lessons()
                ->whereKey($lesson->id)
                ->exists()
        );
    }

    public function test_lesson_concept_casts_is_core_to_boolean(): void
    {
        $curriculumSubject = $this->makeCurriculumSubject();
        $lesson = $this->makeLesson($curriculumSubject);
        $concept = $this->makeConcept($curriculumSubject);

        $link = LessonConcept::create([
            'lesson_id' => $lesson->id,
            'concept_id' => $concept->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        $this->assertTrue($link->refresh()->is_core);
    }

    public function test_lesson_rejects_concept_from_another_curriculum_subject(): void
    {
        $lessonCurriculumSubject =
            $this->makeCurriculumSubject();

        $otherCurriculumSubject =
            $this->makeCurriculumSubject();

        $lesson = $this->makeLesson(
            $lessonCurriculumSubject
        );

        $foreignConcept = $this->makeConcept(
            $otherCurriculumSubject,
            'Concept din altă disciplină'
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Lesson and concept must belong to the same curriculum subject.'
        );

        LessonConcept::create([
            'lesson_id' => $lesson->id,
            'concept_id' => $foreignConcept->id,
            'display_order' => 1,
            'is_core' => true,
        ]);
    }

    public function test_same_concept_cannot_be_linked_twice_to_same_lesson(): void
    {
        $curriculumSubject = $this->makeCurriculumSubject();
        $lesson = $this->makeLesson($curriculumSubject);
        $concept = $this->makeConcept($curriculumSubject);

        LessonConcept::create([
            'lesson_id' => $lesson->id,
            'concept_id' => $concept->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        $this->expectException(QueryException::class);

        LessonConcept::create([
            'lesson_id' => $lesson->id,
            'concept_id' => $concept->id,
            'display_order' => 2,
            'is_core' => false,
        ]);
    }

    public function test_lesson_version_resources_are_ordered_and_metadata_is_cast(): void
    {
        $curriculumSubject = $this->makeCurriculumSubject();
        $lesson = $this->makeLesson($curriculumSubject);
        $version = $this->makeLessonVersion($lesson);

        $explanation = $this->makeResource('explanation');
        $exercise = $this->makeResource('exercise');

        $explanationLink = LessonVersionResource::create([
            'lesson_version_id' => $version->id,
            'resource_id' => $explanation->id,
            'role' => LessonResourceRole::Explanation,
            'display_order' => 20,
            'is_required' => true,
        ]);

        LessonVersionResource::create([
            'lesson_version_id' => $version->id,
            'resource_id' => $exercise->id,
            'role' => LessonResourceRole::Exercise,
            'display_order' => 10,
            'is_required' => false,
        ]);

        $resources = $version->resources()->get();

        $this->assertSame(
            [
                $exercise->id,
                $explanation->id,
            ],
            $resources->pluck('id')->all()
        );

        $this->assertSame(
            LessonResourceRole::Explanation,
            $explanationLink->refresh()->role
        );

        $this->assertTrue(
            $explanationLink->is_required
        );

        $this->assertTrue(
            $explanation
                ->lessonVersions()
                ->whereKey($version->id)
                ->exists()
        );
    }

    public function test_same_resource_cannot_be_linked_twice_to_same_lesson_version(): void
    {
        $curriculumSubject = $this->makeCurriculumSubject();
        $lesson = $this->makeLesson($curriculumSubject);
        $version = $this->makeLessonVersion($lesson);
        $resource = $this->makeResource();

        LessonVersionResource::create([
            'lesson_version_id' => $version->id,
            'resource_id' => $resource->id,
            'role' => LessonResourceRole::Explanation,
            'display_order' => 1,
            'is_required' => true,
        ]);

        $this->expectException(QueryException::class);

        LessonVersionResource::create([
            'lesson_version_id' => $version->id,
            'resource_id' => $resource->id,
            'role' => LessonResourceRole::Supplementary,
            'display_order' => 2,
            'is_required' => false,
        ]);
    }

    public function test_deleting_lesson_cascades_content_links(): void
    {
        $curriculumSubject = $this->makeCurriculumSubject();
        $lesson = $this->makeLesson($curriculumSubject);
        $concept = $this->makeConcept($curriculumSubject);
        $version = $this->makeLessonVersion($lesson);
        $resource = $this->makeResource();

        LessonConcept::create([
            'lesson_id' => $lesson->id,
            'concept_id' => $concept->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        LessonVersionResource::create([
            'lesson_version_id' => $version->id,
            'resource_id' => $resource->id,
            'role' => LessonResourceRole::Explanation,
            'display_order' => 1,
            'is_required' => true,
        ]);

        $lesson->delete();

        $this->assertDatabaseMissing('lesson_concepts', [
            'lesson_id' => $lesson->id,
        ]);

        $this->assertDatabaseMissing('lesson_versions', [
            'id' => $version->id,
        ]);

        $this->assertDatabaseMissing(
            'lesson_version_resources',
            [
                'lesson_version_id' => $version->id,
            ]
        );

        $this->assertDatabaseHas('concepts', [
            'id' => $concept->id,
        ]);

        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
        ]);
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

        $curriculumVersion = CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST-'.$suffix,
            'status' => 'active',
        ]);

        $educationLevel = EducationLevel::create([
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
            'curriculum_version_id' =>
                $curriculumVersion->id,
            'education_level_id' =>
                $educationLevel->id,
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

    private function makeLessonVersion(
        Lesson $lesson
    ): LessonVersion {
        return LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 1,
            'summary' => 'Versiune de test',
            'language_code' => 'ro',
            'status' => 'draft',
        ]);
    }

    private function makeResource(
        string $type = 'explanation'
    ): Resource {
        return Resource::create([
            'code' => 'resource-'.uniqid(),
            'type' => $type,
            'status' => 'active',
        ]);
    }
}
