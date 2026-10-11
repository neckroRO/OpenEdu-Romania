<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\Concept;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\Domain;
use App\Models\EducationLevel;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurriculumDomainConceptProposalEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_propose_domain_creation(): void
    {
        $teacher = $this->teacher();
        $curriculumSubject = $this->curriculumSubject();

        Sanctum::actingAs($teacher);

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'domain',
                'proposal_type' => 'create',
                'payload' => [
                    'curriculum_subject_id' =>
                        $curriculumSubject->id,
                    'title' => 'Algebră',
                    'description' =>
                        'Structuri și operații algebrice.',
                    'display_order' => 10,
                ],
                'reason' => 'Domeniu curricular necesar.',
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.entity_type',
                'domain'
            )
            ->assertJsonPath(
                'data.proposal_type',
                'create'
            )
            ->assertJsonPath(
                'data.payload.title',
                'Algebră'
            )
            ->assertJsonPath(
                'data.status',
                'pending'
            );
    }

    public function test_domain_creation_requires_scope_and_title(): void
    {
        Sanctum::actingAs($this->teacher());

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'domain',
                'proposal_type' => 'create',
                'payload' => [
                    'description' => 'Incomplet.',
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            )
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'fields' => [
                            'payload.curriculum_subject_id',
                            'payload.title',
                        ],
                    ],
                ],
            ]);
    }

    public function test_domain_creation_rejects_parent_from_another_curriculum_subject(): void
    {
        $curriculumSubject =
            $this->curriculumSubject();

        $otherCurriculumSubject =
            $this->curriculumSubject();

        $foreignParent = Domain::create([
            'curriculum_subject_id' =>
                $otherCurriculumSubject->id,
            'title' => 'Capitol străin',
            'description' => null,
            'display_order' => 1,
        ]);

        Sanctum::actingAs($this->teacher());

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'domain',
                'proposal_type' => 'create',
                'payload' => [
                    'curriculum_subject_id' =>
                        $curriculumSubject->id,
                    'parent_domain_id' =>
                        $foreignParent->id,
                    'title' => 'Subcapitol',
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            )
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'fields' => [
                            'payload.parent_domain_id',
                        ],
                    ],
                ],
            ]);
    }

    public function test_teacher_can_propose_domain_update(): void
    {
        $teacher = $this->teacher();
        $domain = $this->domain();

        Sanctum::actingAs($teacher);

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'domain',
                'entity_id' => $domain->id,
                'proposal_type' => 'update',
                'payload' => [
                    'title' => 'Algebră și structuri',
                ],
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.entity_id',
                $domain->id
            )
            ->assertJsonPath(
                'data.payload.title',
                'Algebră și structuri'
            );
    }

    public function test_domain_update_rejects_parent_from_another_curriculum_subject(): void
    {
        $domain = $this->domain();

        $otherCurriculumSubject =
            $this->curriculumSubject();

        $foreignParent = Domain::create([
            'curriculum_subject_id' =>
                $otherCurriculumSubject->id,
            'title' => 'Capitol străin',
            'description' => null,
            'display_order' => 1,
        ]);

        Sanctum::actingAs($this->teacher());

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'domain',
                'entity_id' => $domain->id,
                'proposal_type' => 'update',
                'payload' => [
                    'parent_domain_id' =>
                        $foreignParent->id,
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            )
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'fields' => [
                            'payload.parent_domain_id',
                        ],
                    ],
                ],
            ]);
    }

    public function test_domain_merge_candidate_is_rejected(): void
    {
        $domain = $this->domain();

        Sanctum::actingAs($this->teacher());

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'domain',
                'entity_id' => $domain->id,
                'proposal_type' => 'merge_candidate',
                'payload' => [
                    'duplicate_entity_id' => $domain->id,
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            )
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'fields' => [
                            'proposal_type',
                        ],
                    ],
                ],
            ]);
    }

    public function test_teacher_can_propose_concept_creation(): void
    {
        $domain = $this->domain();

        Sanctum::actingAs($this->teacher());

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'concept',
                'proposal_type' => 'create',
                'payload' => [
                    'code' => 'linear-equation',
                    'title' => 'Ecuație liniară',
                    'description' =>
                        'Ecuație de gradul întâi.',
                    'domain_id' => $domain->id,
                ],
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.entity_type',
                'concept'
            )
            ->assertJsonPath(
                'data.payload.code',
                'linear-equation'
            )
            ->assertJsonPath(
                'data.payload.title',
                'Ecuație liniară'
            );
    }

    public function test_concept_creation_requires_code_and_title(): void
    {
        Sanctum::actingAs($this->teacher());

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'concept',
                'proposal_type' => 'create',
                'payload' => [
                    'description' => 'Incomplet.',
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            )
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'fields' => [
                            'payload.code',
                            'payload.title',
                            'payload.domain_id',
                        ],
                    ],
                ],
            ]);
    }

    public function test_concept_creation_rejects_unknown_domain(): void
    {
        Sanctum::actingAs($this->teacher());

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'concept',
                'proposal_type' => 'create',
                'payload' => [
                    'code' => 'linear-equation',
                    'title' => 'Ecuație liniară',
                    'domain_id' => 999999,
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            )
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'fields' => [
                            'payload.domain_id',
                        ],
                    ],
                ],
            ]);
    }

    public function test_teacher_can_propose_concept_update(): void
    {
        $concept = Concept::create([
            'code' => 'linear-equation',
            'title' => 'Ecuație liniară',
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->teacher());

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'concept',
                'entity_id' => $concept->id,
                'proposal_type' => 'update',
                'payload' => [
                    'title' => 'Ecuație de gradul întâi',
                ],
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.entity_id',
                $concept->id
            )
            ->assertJsonPath(
                'data.payload.title',
                'Ecuație de gradul întâi'
            );
    }

    public function test_teacher_can_propose_concept_alias(): void
    {
        $concept = Concept::create([
            'code' => 'linear-equation',
            'title' => 'Ecuație liniară',
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->teacher());

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'concept',
                'entity_id' => $concept->id,
                'proposal_type' => 'alias',
                'payload' => [
                    'alias' => 'Ecuație de gradul I',
                ],
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.payload.alias',
                'Ecuație de gradul I'
            );
    }

    public function test_concept_alias_requires_alias_value(): void
    {
        $concept = Concept::create([
            'code' => 'linear-equation',
            'title' => 'Ecuație liniară',
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->teacher());

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'concept',
                'entity_id' => $concept->id,
                'proposal_type' => 'alias',
                'payload' => [
                    'alias' => '',
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            )
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'fields' => [
                            'payload.alias',
                        ],
                    ],
                ],
            ]);
    }

    public function test_teacher_can_propose_concept_merge_candidate(): void
    {
        $teacher = $this->teacher();

        $source = Concept::create([
            'code' => 'linear-equation-community',
            'title' => 'Ecuație de gradul I',
            'status' => 'active',
        ]);

        $target = Concept::create([
            'code' => 'linear-equation',
            'title' => 'Ecuație liniară',
            'status' => 'active',
        ]);

        Sanctum::actingAs($teacher);

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'concept',
                'entity_id' => $source->id,
                'proposal_type' => 'merge_candidate',
                'payload' => [
                    'duplicate_entity_id' => $target->id,
                ],
                'reason' => 'Concept duplicat.',
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.entity_type',
                'concept'
            )
            ->assertJsonPath(
                'data.entity_id',
                $source->id
            )
            ->assertJsonPath(
                'data.proposal_type',
                'merge_candidate'
            )
            ->assertJsonPath(
                'data.payload.duplicate_entity_id',
                $target->id
            )
            ->assertJsonPath(
                'data.status',
                'pending'
            );
    }

    public function test_unknown_domain_or_concept_cannot_be_updated(): void
    {
        Sanctum::actingAs($this->teacher());

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'domain',
                'entity_id' => 999999,
                'proposal_type' => 'update',
                'payload' => [
                    'title' => 'Inexistent',
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            )
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'fields' => [
                            'entity_id',
                        ],
                    ],
                ],
            ]);

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'concept',
                'entity_id' => 999999,
                'proposal_type' => 'update',
                'payload' => [
                    'title' => 'Inexistent',
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            )
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'fields' => [
                            'entity_id',
                        ],
                    ],
                ],
            ]);
    }

    public function test_entity_specific_payload_rejects_unknown_fields(): void
    {
        Sanctum::actingAs($this->teacher());

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'concept',
                'proposal_type' => 'create',
                'payload' => [
                    'code' => 'linear-equation',
                    'title' => 'Ecuație liniară',
                    'curriculum_subject_id' => 123,
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            )
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'fields' => [
                            'payload.curriculum_subject_id',
                        ],
                    ],
                ],
            ]);
    }

    private function teacher(): User
    {
        return User::factory()->create([
            'role' => UserRole::Teacher,
        ]);
    }

    private function domain(): Domain
    {
        $curriculumSubject = $this->curriculumSubject();

        return Domain::create([
            'curriculum_subject_id' =>
                $curriculumSubject->id,
            'title' => 'Algebră',
            'description' => null,
            'display_order' => 10,
        ]);
    }

    private function curriculumSubject(): CurriculumSubject
    {
        $curriculum = Curriculum::create([
            'code' => 'TEST-'.uniqid(),
            'name' => 'Curriculum test',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $version = CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST-'.uniqid(),
            'valid_from' => '2026-09-01',
            'status' => 'active',
        ]);

        $level = EducationLevel::create([
            'code' => 'level-'.uniqid(),
            'name' => 'Nivel test',
            'ordinal' => 1,
            'education_stage' => 'test',
        ]);

        $subject = Subject::create([
            'code' => 'subject-'.uniqid(),
            'name' => 'Matematică test',
            'status' => 'active',
        ]);

        return CurriculumSubject::create([
            'curriculum_version_id' => $version->id,
            'curriculum_framework_variant_id' => null,
            'education_level_id' => $level->id,
            'subject_id' => $subject->id,
            'component' => 'TC',
            'display_order' => 1,
            'status' => 'active',
        ]);
    }
}
