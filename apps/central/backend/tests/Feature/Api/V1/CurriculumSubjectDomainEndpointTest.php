<?php

namespace Tests\Feature\Api\V1;

use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\Domain;
use App\Models\EducationLevel;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumSubjectDomainEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_all_domains_for_a_curriculum_subject(): void
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

        $algebra = Domain::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_domain_id' => null,
            'title' => 'Algebră',
            'description' => 'Concepte de algebră.',
            'display_order' => 1,
        ]);

        $geometry = Domain::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_domain_id' => null,
            'title' => 'Geometrie',
            'description' => null,
            'display_order' => 2,
        ]);

        $otherLevel = EducationLevel::create([
            'code' => 'grade-8',
            'name' => 'Clasa a VIII-a',
            'ordinal' => 8,
            'education_stage' => 'gimnazial',
        ]);

        $otherCurriculumSubject = CurriculumSubject::create([
            'curriculum_version_id' => $version->id,
            'education_level_id' => $otherLevel->id,
            'subject_id' => $subject->id,
            'display_order' => 1,
            'status' => 'active',
        ]);

        Domain::create([
            'curriculum_subject_id' => $otherCurriculumSubject->id,
            'parent_domain_id' => null,
            'title' => 'Alt domeniu',
            'description' => null,
            'display_order' => 1,
        ]);

        $response = $this->getJson(
            "/api/v1/curriculum-subjects/{$curriculumSubject->id}/domains"
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $algebra->id)
            ->assertJsonPath('data.0.title', 'Algebră')
            ->assertJsonPath('data.0.display_order', 1)
            ->assertJsonPath('data.1.id', $geometry->id)
            ->assertJsonPath('data.1.title', 'Geometrie')
            ->assertJsonPath('data.1.display_order', 2)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'display_order',
                    ],
                ],
                'meta' => [
                    'request_id',
                ],
            ]);

        $requestId =
            $response->headers->get(
                'X-Request-ID'
            );

        $response->assertJsonPath(
            'meta.request_id',
            $requestId
        );
    }
}
