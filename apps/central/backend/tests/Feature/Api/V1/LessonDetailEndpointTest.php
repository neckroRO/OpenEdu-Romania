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
use App\Models\LessonVersion;
use App\Models\LessonVersionResource;
use App\Models\Resource;
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

    public function test_it_returns_lesson_concepts_and_only_resources_from_latest_published_version(): void
    {
        $lesson = $this->makeLesson();

        $concept = Concept::create([
            'code' => 'concept-public',
            'title' => 'Concept public',
            'description' => 'Concept asociat lecției',
            'status' => 'active',
        ]);

        $domain = Domain::create([
            'curriculum_subject_id' =>
                $lesson->curriculum_subject_id,
            'title' => 'Domeniu test',
            'description' =>
                'Domeniu pentru conceptul lecției',
            'display_order' => 1,
        ]);

        ConceptPlacement::create([
            'concept_id' => $concept->id,
            'domain_id' => $domain->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        LessonConcept::create([
            'lesson_id' => $lesson->id,
            'concept_id' => $concept->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        $oldPublishedVersion = LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 1,
            'summary' => 'Versiune publicată veche',
            'content' => 'Conținut vechi',
            'language_code' => 'ro',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $latestPublishedVersion = LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 2,
            'summary' => 'Versiune publicată curentă',
            'content' => 'Conținut public',
            'language_code' => 'ro',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $draftVersion = LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 3,
            'summary' => 'Draft',
            'content' => 'Conținut draft',
            'language_code' => 'ro',
            'status' => 'draft',
        ]);

        $oldResource = Resource::create([
            'code' => 'resource-old',
            'type' => 'article',
            'status' => 'active',
        ]);

        $publicResource = Resource::create([
            'code' => 'resource-public',
            'type' => 'article',
            'status' => 'active',
        ]);

        $draftResource = Resource::create([
            'code' => 'resource-draft',
            'type' => 'article',
            'status' => 'active',
        ]);

        LessonVersionResource::create([
            'lesson_version_id' =>
                $oldPublishedVersion->id,
            'resource_id' => $oldResource->id,
            'role' => 'supplementary',
            'display_order' => 1,
            'is_required' => false,
        ]);

        LessonVersionResource::create([
            'lesson_version_id' =>
                $latestPublishedVersion->id,
            'resource_id' => $publicResource->id,
            'role' => 'explanation',
            'display_order' => 1,
            'is_required' => true,
        ]);

        LessonVersionResource::create([
            'lesson_version_id' => $draftVersion->id,
            'resource_id' => $draftResource->id,
            'role' => 'exercise',
            'display_order' => 1,
            'is_required' => true,
        ]);

        $this->getJson(
            "/api/v1/lessons/{$lesson->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.concepts.0.concept.code',
                'concept-public'
            )
            ->assertJsonPath(
                'data.concepts.0.display_order',
                1
            )
            ->assertJsonPath(
                'data.concepts.0.is_core',
                true
            )
            ->assertJsonPath(
                'data.version.version_number',
                2
            )
            ->assertJsonCount(
                1,
                'data.version.resources'
            )
            ->assertJsonPath(
                'data.version.resources.0.role',
                'explanation'
            )
            ->assertJsonPath(
                'data.version.resources.0.is_required',
                true
            )
            ->assertJsonPath(
                'data.version.resources.0.resource.code',
                'resource-public'
            )
            ->assertJsonMissing([
                'code' => 'resource-old',
            ])
            ->assertJsonMissing([
                'code' => 'resource-draft',
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