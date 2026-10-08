<?php

namespace Tests\Feature\Api\V1;

use App\Enums\CurriculumProposalStatus;
use App\Enums\CurriculumProposalType;
use App\Enums\UserRole;
use App\Models\Concept;
use App\Models\CurriculumProposal;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurriculumProposalModerationEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_preview_merge_candidate(): void
    {
        [$source, $target, $proposal] =
            $this->makeMergeProposal();

        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);

        Sanctum::actingAs($moderator);

        $this->getJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/merge-preview"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.source_subject_id',
                $source->id
            )
            ->assertJsonPath(
                'data.target_subject_id',
                $target->id
            )
            ->assertJsonPath(
                'data.blocked',
                false
            );
    }

    public function test_teacher_cannot_preview_merge_candidate(): void
    {
        [, , $proposal] = $this->makeMergeProposal();

        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        Sanctum::actingAs($teacher);

        $this->getJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/merge-preview"
        )->assertForbidden();
    }

    public function test_moderator_can_reject_pending_proposal(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'proposal_type' =>
                CurriculumProposalType::Create,
            'payload' => [
                'name' => 'Astronomie',
            ],
            'status' =>
                CurriculumProposalStatus::Pending,
            'proposed_by' => $teacher->id,
        ]);

        Sanctum::actingAs($moderator);

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/reject",
            [
                'note' => 'Necesită revizuire.',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'rejected'
            )
            ->assertJsonPath(
                'data.review_note',
                'Necesită revizuire.'
            );

        $this->assertSame(
            CurriculumProposalStatus::Rejected,
            $proposal->refresh()->status
        );

        $this->assertSame(
            $moderator->id,
            $proposal->reviewed_by
        );
    }

    public function test_admin_can_confirm_merge(): void
    {
        [$source, $target, $proposal] =
            $this->makeMergeProposal();

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/confirm-merge",
            [
                'note' => 'Duplicat confirmat.',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.proposal.status',
                'merged'
            )
            ->assertJsonPath(
                'data.merge.source_entity_id',
                $source->id
            )
            ->assertJsonPath(
                'data.merge.target_entity_id',
                $target->id
            );

        $this->assertSame(
            'merged',
            $source->refresh()->status
        );

        $this->assertSame(
            CurriculumProposalStatus::Merged,
            $proposal->refresh()->status
        );
    }

    public function test_moderator_can_preview_concept_merge_candidate(): void
    {
        [$source, $target, $proposal] =
            $this->makeConceptMergeProposal();

        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);

        Sanctum::actingAs($moderator);

        $this->getJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/merge-preview"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.source_concept_id',
                $source->id
            )
            ->assertJsonPath(
                'data.target_concept_id',
                $target->id
            )
            ->assertJsonPath(
                'data.blocked',
                false
            )
            ->assertJsonStructure([
                'data' => [
                    'placements_to_move',
                    'placements_to_deduplicate',
                    'competencies_to_move',
                    'competencies_to_deduplicate',
                    'lessons_to_move',
                    'lessons_to_deduplicate',
                    'resources_to_move',
                    'resources_to_deduplicate',
                    'aliases_to_move',
                    'aliases_to_deduplicate',
                    'source_title_alias_will_be_created',
                    'blocking_reasons',
                ],
            ]);
    }

    public function test_admin_can_confirm_concept_merge(): void
    {
        [$source, $target, $proposal] =
            $this->makeConceptMergeProposal();

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/confirm-merge",
            [
                'note' => 'Concept duplicat confirmat.',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.proposal.status',
                'merged'
            )
            ->assertJsonPath(
                'data.merge.entity_type',
                'concept'
            )
            ->assertJsonPath(
                'data.merge.source_entity_id',
                $source->id
            )
            ->assertJsonPath(
                'data.merge.target_entity_id',
                $target->id
            );

        $this->assertSame(
            'merged',
            $source->refresh()->status
        );

        $this->assertSame(
            'active',
            $target->refresh()->status
        );

        $this->assertSame(
            CurriculumProposalStatus::Merged,
            $proposal->refresh()->status
        );

        $this->assertDatabaseHas(
            'curriculum_entity_merges',
            [
                'entity_type' => 'concept',
                'source_entity_id' => $source->id,
                'target_entity_id' => $target->id,
                'merged_by' => $admin->id,
            ]
        );

        $this->assertDatabaseHas(
            'curriculum_audit_events',
            [
                'event_type' => 'proposal_merged',
                'proposal_id' => $proposal->id,
                'actor_id' => $admin->id,
            ]
        );

        $this->assertDatabaseHas(
            'curriculum_audit_events',
            [
                'event_type' => 'merge_completed',
                'proposal_id' => $proposal->id,
                'entity_type' => 'concept',
                'entity_id' => $target->id,
                'actor_id' => $admin->id,
            ]
        );
    }

    public function test_teacher_cannot_reject_proposal(): void
    {
        [, , $proposal] = $this->makeMergeProposal();

        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        Sanctum::actingAs($teacher);

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/reject"
        )->assertForbidden();
    }

    public function test_teacher_cannot_confirm_merge(): void
    {
        [, , $proposal] = $this->makeMergeProposal();

        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        Sanctum::actingAs($teacher);

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/confirm-merge"
        )->assertForbidden();
    }

    public function test_rejected_proposal_cannot_be_rejected_again(): void
    {
        [, , $proposal] = $this->makeMergeProposal();

        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);

        $proposal->update([
            'status' =>
                CurriculumProposalStatus::Rejected,
        ]);

        Sanctum::actingAs($moderator);

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/reject"
        )
            ->assertStatus(409)
            ->assertJsonPath(
                'error.code',
                'CURRICULUM_PROPOSAL_REVIEW_INVALID'
            );
    }

    public function test_unauthenticated_user_cannot_moderate(): void
    {
        [, , $proposal] = $this->makeMergeProposal();

        $this->getJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/merge-preview"
        )->assertUnauthorized();

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/reject"
        )->assertUnauthorized();

        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposal->id}/confirm-merge"
        )->assertUnauthorized();
    }

    private function makeConceptMergeProposal(): array
    {
        $source = Concept::create([
            'code' => 'linear-equation-community-'.uniqid(),
            'title' => 'Ecuație de gradul I',
            'status' => 'active',
        ]);

        $target = Concept::create([
            'code' => 'linear-equation-'.uniqid(),
            'title' => 'Ecuație liniară',
            'status' => 'active',
        ]);

        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        $proposal = CurriculumProposal::create([
            'entity_type' => 'concept',
            'entity_id' => $source->id,
            'proposal_type' =>
                CurriculumProposalType::MergeCandidate,
            'payload' => [
                'duplicate_entity_id' => $target->id,
            ],
            'reason' => 'Concept duplicat.',
            'status' =>
                CurriculumProposalStatus::Pending,
            'proposed_by' => $teacher->id,
        ]);

        return [
            $source,
            $target,
            $proposal,
        ];
    }

    private function makeMergeProposal(): array
    {
        $source = Subject::create([
            'code' => 'math-community-'.uniqid(),
            'name' => 'Mate',
            'status' => 'active',
        ]);

        $target = Subject::create([
            'code' => 'mathematics-'.uniqid(),
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'entity_id' => $source->id,
            'proposal_type' =>
                CurriculumProposalType::MergeCandidate,
            'payload' => [
                'duplicate_entity_id' => $target->id,
            ],
            'status' =>
                CurriculumProposalStatus::Pending,
            'proposed_by' => $teacher->id,
        ]);

        return [
            $source,
            $target,
            $proposal,
        ];
    }
}
