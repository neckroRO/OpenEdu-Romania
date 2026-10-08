<?php

namespace Tests\Feature\Api\V1;

use App\Enums\CurriculumProposalStatus;
use App\Enums\CurriculumProposalType;
use App\Enums\UserRole;
use App\Models\Concept;
use App\Models\Curriculum;
use App\Models\CurriculumProposal;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\Domain;
use App\Models\EducationLevel;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurriculumProposalApplicationEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_apply_domain_create_proposal(): void
    {
        $teacher = $this->teacher();
        $moderator = $this->moderator();
        $curriculumSubject = $this->curriculumSubject();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'domain',
            'entity_id' => null,
            'proposal_type' => CurriculumProposalType::Create,
            'payload' => [
                'curriculum_subject_id' => $curriculumSubject->id,
                'title' => 'Algebră',
                'description' => 'Structuri algebrice.',
                'display_order' => 10,
            ],
            'reason' => 'Domeniu necesar.',
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $teacher->id,
        ]);

        Sanctum::actingAs($moderator);

        $response = $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/approve",
            [
                'note' => 'Aprobat.',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'approved'
            )
            ->assertJsonPath(
                'data.reviewed_by',
                $moderator->id
            );

        $proposal->refresh();

        $this->assertNotNull($proposal->entity_id);

        $this->assertDatabaseHas('domains', [
            'id' => $proposal->entity_id,
            'curriculum_subject_id' => $curriculumSubject->id,
            'title' => 'Algebră',
            'display_order' => 10,
        ]);

        $this->assertDatabaseHas('curriculum_audit_events', [
            'event_type' => 'proposal_approved',
            'proposal_id' => $proposal->id,
            'actor_id' => $moderator->id,
        ]);

        $this->assertDatabaseHas('curriculum_audit_events', [
            'event_type' => 'proposal_applied',
            'proposal_id' => $proposal->id,
            'entity_type' => 'domain',
            'entity_id' => $proposal->entity_id,
            'actor_id' => $moderator->id,
        ]);
    }

    public function test_moderator_can_apply_domain_update_proposal(): void
    {
        $teacher = $this->teacher();
        $moderator = $this->moderator();
        $domain = $this->domain();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'domain',
            'entity_id' => $domain->id,
            'proposal_type' => CurriculumProposalType::Update,
            'payload' => [
                'title' => 'Algebră și structuri',
                'description' => 'Descriere actualizată.',
            ],
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $teacher->id,
        ]);

        Sanctum::actingAs($moderator);

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/approve"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'approved'
            );

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'title' => 'Algebră și structuri',
            'description' => 'Descriere actualizată.',
        ]);
    }

    public function test_moderator_can_apply_concept_create_proposal(): void
    {
        $teacher = $this->teacher();
        $moderator = $this->moderator();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'concept',
            'entity_id' => null,
            'proposal_type' => CurriculumProposalType::Create,
            'payload' => [
                'code' => 'linear-equation',
                'title' => 'Ecuație liniară',
                'description' => 'Ecuație de gradul întâi.',
            ],
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $teacher->id,
        ]);

        Sanctum::actingAs($moderator);

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/approve"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'approved'
            );

        $proposal->refresh();

        $this->assertNotNull($proposal->entity_id);

        $this->assertDatabaseHas('concepts', [
            'id' => $proposal->entity_id,
            'code' => 'linear-equation',
            'title' => 'Ecuație liniară',
            'status' => 'active',
        ]);
    }

    public function test_moderator_can_apply_concept_update_proposal(): void
    {
        $teacher = $this->teacher();
        $moderator = $this->moderator();

        $concept = Concept::create([
            'code' => 'linear-equation',
            'title' => 'Ecuație liniară',
            'status' => 'active',
        ]);

        $proposal = CurriculumProposal::create([
            'entity_type' => 'concept',
            'entity_id' => $concept->id,
            'proposal_type' => CurriculumProposalType::Update,
            'payload' => [
                'title' => 'Ecuație de gradul întâi',
                'description' => 'Formulare actualizată.',
            ],
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $teacher->id,
        ]);

        Sanctum::actingAs($moderator);

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/approve"
        )
            ->assertOk();

        $this->assertDatabaseHas('concepts', [
            'id' => $concept->id,
            'title' => 'Ecuație de gradul întâi',
            'description' => 'Formulare actualizată.',
        ]);
    }

    public function test_moderator_can_apply_concept_alias_proposal(): void
    {
        $teacher = $this->teacher();
        $moderator = $this->moderator();

        $concept = Concept::create([
            'code' => 'linear-equation',
            'title' => 'Ecuație liniară',
            'status' => 'active',
        ]);

        $proposal = CurriculumProposal::create([
            'entity_type' => 'concept',
            'entity_id' => $concept->id,
            'proposal_type' => CurriculumProposalType::Alias,
            'payload' => [
                'alias' => 'Ecuație de gradul I',
            ],
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $teacher->id,
        ]);

        Sanctum::actingAs($moderator);

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/approve",
            [
                'note' => 'Alias valid.',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'approved'
            );

        $this->assertDatabaseHas('curriculum_aliases', [
            'entity_type' => 'concept',
            'entity_id' => $concept->id,
            'alias' => 'Ecuație de gradul I',
            'normalized_alias' => 'ecuatie de gradul i',
            'source' => 'community',
            'status' => 'approved',
            'created_by' => $teacher->id,
            'approved_by' => $moderator->id,
        ]);

        $this->assertDatabaseHas('curriculum_audit_events', [
            'event_type' => 'proposal_applied',
            'proposal_id' => $proposal->id,
            'entity_type' => 'concept',
            'entity_id' => $concept->id,
            'actor_id' => $moderator->id,
        ]);
    }

    public function test_teacher_cannot_approve_proposal(): void
    {
        $teacher = $this->teacher();
        $domain = $this->domain();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'domain',
            'entity_id' => $domain->id,
            'proposal_type' => CurriculumProposalType::Update,
            'payload' => [
                'title' => 'Titlu nou',
            ],
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $teacher->id,
        ]);

        Sanctum::actingAs($teacher);

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/approve"
        )->assertForbidden();

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'title' => 'Algebră',
        ]);
    }

    public function test_proposal_cannot_be_applied_twice(): void
    {
        $teacher = $this->teacher();
        $moderator = $this->moderator();
        $domain = $this->domain();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'domain',
            'entity_id' => $domain->id,
            'proposal_type' => CurriculumProposalType::Update,
            'payload' => [
                'title' => 'Algebră modernă',
            ],
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $teacher->id,
        ]);

        Sanctum::actingAs($moderator);

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/approve"
        )->assertOk();

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/approve"
        )
            ->assertStatus(409)
            ->assertJsonPath(
                'error.code',
                'CURRICULUM_PROPOSAL_APPLICATION_INVALID'
            );
    }

    public function test_subject_proposal_is_not_applied_by_domain_concept_flow(): void
    {
        $teacher = $this->teacher();
        $moderator = $this->moderator();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'entity_id' => null,
            'proposal_type' => CurriculumProposalType::Create,
            'payload' => [
                'code' => 'astronomy',
                'name' => 'Astronomie',
            ],
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $teacher->id,
        ]);

        Sanctum::actingAs($moderator);

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/approve"
        )
            ->assertStatus(409)
            ->assertJsonPath(
                'error.code',
                'CURRICULUM_PROPOSAL_APPLICATION_INVALID'
            );

        $proposal->refresh();

        $this->assertSame(
            CurriculumProposalStatus::Pending,
            $proposal->status
        );
    }

    private function teacher(): User
    {
        return User::factory()->create([
            'role' => UserRole::Teacher,
        ]);
    }

    private function moderator(): User
    {
        return User::factory()->create([
            'role' => UserRole::Moderator,
        ]);
    }

    private function domain(): Domain
    {
        $curriculumSubject = $this->curriculumSubject();

        return Domain::create([
            'curriculum_subject_id' => $curriculumSubject->id,
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
