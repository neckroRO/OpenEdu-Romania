<?php

namespace Tests\Feature\Api\V1;

use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EducationLevelSubjectEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_subjects_for_an_education_level(): void
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
            'description' => 'Matematică pentru învățământul gimnazial.',
            'status' => 'active',
        ]);

        $curriculumSubject = CurriculumSubject::create([
            'curriculum_version_id' => $version->id,
            'education_level_id' => $level->id,
            'subject_id' => $subject->id,
            'display_order' => 1,
            'status' => 'active',
            'program_reference' => 'OMEN nr. 3393/28.02.2017',
            'program_source_url' =>
                'https://www.edu.ro/Ordin_ministru_3393_2017',
            'program_approved_at' => '2017-02-28',
        ]);

        $response = $this->getJson(
            "/api/v1/education-levels/{$level->id}/subjects"
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $curriculumSubject->id
            )
            ->assertJsonPath(
                'data.0.display_order',
                1
            )
            ->assertJsonPath(
                'data.0.program.reference',
                'OMEN nr. 3393/28.02.2017'
            )
            ->assertJsonPath(
                'data.0.program.source_url',
                'https://www.edu.ro/Ordin_ministru_3393_2017'
            )
            ->assertJsonPath(
                'data.0.program.approved_at',
                '2017-02-28'
            )
            ->assertJsonPath(
                'data.0.subject.id',
                $subject->id
            )
            ->assertJsonPath(
                'data.0.subject.code',
                'mathematics'
            )
            ->assertJsonPath(
                'data.0.subject.name',
                'Matematică'
            )
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'display_order',
                        'program' => [
                            'reference',
                            'source_url',
                            'approved_at',
                        ],
                        'subject' => [
                            'id',
                            'code',
                            'name',
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
