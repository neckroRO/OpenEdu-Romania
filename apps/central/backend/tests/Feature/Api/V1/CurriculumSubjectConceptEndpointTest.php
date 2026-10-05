<?php

namespace Tests\Feature\Api\V1;

use App\Models\Concept;
use App\Models\ConceptPlacement;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\Domain;
use App\Models\EducationLevel;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumSubjectConceptEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_concepts_for_a_curriculum_subject(): void
    {
        $curriculum = Curriculum::create([
            'code' => 'RO-NATIONAL',
            'name' => 'Curriculum național România',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $version = CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST',
            'status' => 'active',
        ]);

        $level = EducationLevel::create([
            'code' => 'grade-7',
            'name' => 'Clasa a VII-a',
            'ordinal' => 7,
            'education_stage' => 'gimnazial',
        ]);

        $subject = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        $curriculumSubject = CurriculumSubject::create([
            'curriculum_version_id' => $version->id,
            'education_level_id' => $level->id,
            'subject_id' => $subject->id,
            'display_order' => 1,
            'status' => 'active',
        ]);

        $domain = Domain::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_domain_id' => null,
            'title' => 'Algebră',
            'description' => 'Concepte fundamentale de algebră.',
            'display_order' => 1,
        ]);

        $concept = Concept::create([
            'code' => 'linear-equations',
            'title' => 'Ecuații de gradul I',
            'description' => 'Ecuații de gradul I cu o necunoscută.',
            'status' => 'active',
        ]);

        $placement = ConceptPlacement::create([
            'concept_id' => $concept->id,
            'domain_id' => $domain->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        $response = $this->getJson(
            "/api/v1/curriculum-subjects/{$curriculumSubject->id}/concepts"
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $placement->id)
            ->assertJsonPath('data.0.display_order', 1)
            ->assertJsonPath('data.0.is_core', true)
            ->assertJsonPath('data.0.domain.title', 'Algebră')
            ->assertJsonPath(
                'data.0.concept.code',
                'linear-equations'
            )
            ->assertJsonPath(
                'data.0.concept.title',
                'Ecuații de gradul I'
            )
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'display_order',
                        'is_core',
                        'domain' => [
                            'id',
                            'title',
                            'description',
                            'display_order',
                        ],
                        'concept' => [
                            'id',
                            'code',
                            'title',
                            'description',
                        ],
                    ],
                ],
                'meta' => [
                    'request_id',
                ],
            ]);

        $requestId = $response->headers->get('X-Request-ID');

        $response->assertJsonPath(
            'meta.request_id',
            $requestId
        );
    }
}
