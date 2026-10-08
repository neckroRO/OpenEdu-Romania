<?php

namespace Tests\Feature;

use App\Enums\CurriculumProposalStatus;
use App\Enums\CurriculumProposalType;
use App\Models\CurriculumProposal;
use App\Models\Subject;
use App\Models\User;
use App\Services\Curriculum\CurriculumMergeModerationService;
use App\Services\Curriculum\CurriculumProposalService;
use App\Services\Curriculum\CurriculumProposalWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_proposal_creation_is_audited(): void
    {
        $user = User::factory()->create();

        $proposal = app(
            CurriculumProposalService::class
        )->create(
            proposer: $user,
            entityType: 'subject',
            type: CurriculumProposalType::Create,
            payload: [
                'name' => 'Astronomie',
            ],
            reason: 'Materie nouă.'
        );

        $this->assertDatabaseHas(
            'curriculum_audit_events',
            [
                'event_type' => 'proposal_created',
                'proposal_id' => $proposal->id,
                'actor_id' => $user->id,
                'entity_type' => 'subject',
                'entity_id' => null,
            ]
        );
    }

    public function test_proposal_rejection_is_audited(): void
    {
        $proposer = User::factory()->create();
        $reviewer = User::factory()->create();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'proposal_type' =>
                CurriculumProposalType::Create,
            'payload' => [
                'name' => 'Astronomie',
            ],
            'status' =>
                CurriculumProposalStatus::Pending,
            'proposed_by' => $proposer->id,
        ]);

        app(
            CurriculumProposalWorkflow::class
        )->reject(
            $proposal,
            $reviewer,
            'Necesită revizuire.'
        );

        $this->assertDatabaseHas(
            'curriculum_audit_events',
            [
                'event_type' => 'proposal_rejected',
                'proposal_id' => $proposal->id,
                'actor_id' => $reviewer->id,
            ]
        );
    }

    public function test_proposal_approval_is_audited(): void
    {
        $proposer = User::factory()->create();
        $reviewer = User::factory()->create();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'proposal_type' =>
                CurriculumProposalType::Create,
            'payload' => [
                'name' => 'Astronomie',
            ],
            'status' =>
                CurriculumProposalStatus::Pending,
            'proposed_by' => $proposer->id,
        ]);

        app(
            CurriculumProposalWorkflow::class
        )->approve(
            $proposal,
            $reviewer,
            'Aprobat.'
        );

        $this->assertDatabaseHas(
            'curriculum_audit_events',
            [
                'event_type' => 'proposal_approved',
                'proposal_id' => $proposal->id,
                'actor_id' => $reviewer->id,
            ]
        );
    }

    public function test_completed_merge_has_audit_trail(): void
    {
        $source = Subject::create([
            'code' => 'math-community',
            'name' => 'Mate',
            'status' => 'active',
        ]);

        $target = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        $proposer = User::factory()->create();
        $moderator = User::factory()->create();

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
            'proposed_by' => $proposer->id,
        ]);

        app(
            CurriculumMergeModerationService::class
        )->confirm(
            $proposal,
            $moderator,
            'Duplicat confirmat.'
        );

        $this->assertDatabaseHas(
            'curriculum_audit_events',
            [
                'event_type' => 'proposal_merged',
                'proposal_id' => $proposal->id,
                'actor_id' => $moderator->id,
            ]
        );

        $this->assertDatabaseHas(
            'curriculum_audit_events',
            [
                'event_type' => 'merge_completed',
                'proposal_id' => $proposal->id,
                'actor_id' => $moderator->id,
                'entity_type' => 'subject',
                'entity_id' => $target->id,
            ]
        );
    }

    public function test_failed_review_does_not_create_audit_event(): void
    {
        $proposer = User::factory()->create();
        $reviewer = User::factory()->create();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'proposal_type' =>
                CurriculumProposalType::Create,
            'payload' => [
                'name' => 'Astronomie',
            ],
            'status' =>
                CurriculumProposalStatus::Rejected,
            'proposed_by' => $proposer->id,
        ]);

        try {
            app(
                CurriculumProposalWorkflow::class
            )->reject(
                $proposal,
                $reviewer
            );

            $this->fail(
                'Expected workflow rejection was not thrown.'
            );
        } catch (\DomainException) {
        }

        $this->assertDatabaseCount(
            'curriculum_audit_events',
            0
        );
    }
}
