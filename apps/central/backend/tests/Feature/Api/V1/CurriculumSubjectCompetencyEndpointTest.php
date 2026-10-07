<?php

namespace Tests\Feature\Api\V1;

use App\Models\Competency;
use App\Models\CompetencyConcept;
use App\Models\Concept;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumSubjectCompetencyEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_active_competencies_as_an_ordered_tree(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $secondGeneral = Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => null,
            'code' => '2',
            'type' => 'general',
            'title' => 'A doua competență generală',
            'display_order' => 2,
            'status' => 'active',
        ]);

        $firstGeneral = Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => null,
            'code' => '1',
            'type' => 'general',
            'title' => 'Prima competență generală',
            'display_order' => 1,
            'status' => 'active',
        ]);

        $secondSpecific = Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => $firstGeneral->id,
            'code' => '1.2',
            'type' => 'specific',
            'title' => 'A doua competență specifică',
            'display_order' => 2,
            'status' => 'active',
        ]);

        $firstSpecific = Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => $firstGeneral->id,
            'code' => '1.1',
            'type' => 'specific',
            'title' => 'Prima competență specifică',
            'display_order' => 1,
            'status' => 'active',
        ]);

        Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => null,
            'code' => '0',
            'type' => 'general',
            'title' => 'Competență inactivă',
            'display_order' => 0,
            'status' => 'inactive',
        ]);

        $response = $this->getJson(
            "/api/v1/curriculum-subjects/{$curriculumSubject->id}/competencies"
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $firstGeneral->id)
            ->assertJsonPath('data.0.code', '1')
            ->assertJsonPath('data.0.type', 'general')
            ->assertJsonPath(
                'data.0.title',
                'Prima competență generală'
            )
            ->assertJsonCount(2, 'data.0.children')
            ->assertJsonPath(
                'data.0.children.0.id',
                $firstSpecific->id
            )
            ->assertJsonPath(
                'data.0.children.0.code',
                '1.1'
            )
            ->assertJsonPath(
                'data.0.children.1.id',
                $secondSpecific->id
            )
            ->assertJsonPath('data.1.id', $secondGeneral->id);

        $requestId = $response->headers->get('X-Request-ID');

        $response->assertJsonPath(
            'meta.request_id',
            $requestId
        );
    }

    public function test_it_lists_only_active_concepts_for_a_competency(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $competency = Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => null,
            'code' => '1',
            'type' => 'general',
            'title' => 'Competență generală',
            'display_order' => 1,
            'status' => 'active',
        ]);

        $secondConcept = Concept::create([
            'code' => 'second-concept',
            'title' => 'Al doilea concept',
            'status' => 'active',
        ]);

        $firstConcept = Concept::create([
            'code' => 'first-concept',
            'title' => 'Primul concept',
            'status' => 'active',
        ]);

        $inactiveConcept = Concept::create([
            'code' => 'inactive-concept',
            'title' => 'Concept inactiv',
            'status' => 'inactive',
        ]);

        CompetencyConcept::create([
            'competency_id' => $competency->id,
            'concept_id' => $secondConcept->id,
            'display_order' => 2,
            'is_core' => false,
        ]);

        CompetencyConcept::create([
            'competency_id' => $competency->id,
            'concept_id' => $firstConcept->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        CompetencyConcept::create([
            'competency_id' => $competency->id,
            'concept_id' => $inactiveConcept->id,
            'display_order' => 0,
            'is_core' => true,
        ]);

        $response = $this->getJson(
            "/api/v1/curriculum-subjects/{$curriculumSubject->id}/competencies"
        );

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data.0.concepts')
            ->assertJsonPath(
                'data.0.concepts.0.concept.id',
                $firstConcept->id
            )
            ->assertJsonPath(
                'data.0.concepts.0.concept.code',
                'first-concept'
            )
            ->assertJsonPath(
                'data.0.concepts.0.is_core',
                true
            )
            ->assertJsonPath(
                'data.0.concepts.1.concept.id',
                $secondConcept->id
            )
            ->assertJsonPath(
                'data.0.concepts.1.is_core',
                false
            );
    }

    public function test_it_returns_an_empty_list_when_subject_has_no_competencies(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $response = $this->getJson(
            "/api/v1/curriculum-subjects/{$curriculumSubject->id}/competencies"
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonCount(0, 'data');
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
            'education_stage' => 'primar',
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
