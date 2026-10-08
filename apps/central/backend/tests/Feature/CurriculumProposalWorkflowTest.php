<?php

namespace Tests\Feature;

use App\Enums\CurriculumProposalStatus;
use App\Enums\CurriculumProposalType;
use App\Models\CurriculumProposal;
use App\Models\User;
use App\Services\Curriculum\CurriculumProposalWorkflow;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumProposalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_proposal_can_be_approved(): void
    {
        $proposer = User::factory()->create();
        $reviewer = User::factory()->create();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'entity_id' => 10,
            'proposal_type' => CurriculumProposalType::Update,
            'payload' => [
                'name' => 'Matematică',
            ],
            'reason' => 'Corectare.',
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $proposer->id,
        ]);

        $result = app(CurriculumProposalWorkflow::class)->approve(
            $proposal,
            $reviewer,
            'Aprobat.'
        );

        $this->assertSame(
            CurriculumProposalStatus::Approved,
            $result->status
        );

        $this->assertSame(
            $reviewer->id,
            $result->reviewed_by
        );

        $this->assertNotNull(
            $result->reviewed_at
        );

        $this->assertSame(
            'Aprobat.',
            $result->review_note
        );
    }

    public function test_pending_proposal_can_be_rejected(): void
    {
        $proposer = User::factory()->create();
        $reviewer = User::factory()->create();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'entity_id' => 10,
            'proposal_type' => CurriculumProposalType::Update,
            'payload' => [
                'name' => 'Alt nume',
            ],
            'reason' => 'Propunere.',
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $proposer->id,
        ]);

        $result = app(CurriculumProposalWorkflow::class)->reject(
            $proposal,
            $reviewer,
            'Nu corespunde nomenclatorului oficial.'
        );

        $this->assertSame(
            CurriculumProposalStatus::Rejected,
            $result->status
        );
    }

    public function test_pending_proposal_can_be_marked_as_merged(): void
    {
        $proposer = User::factory()->create();
        $reviewer = User::factory()->create();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'entity_id' => 10,
            'proposal_type' => CurriculumProposalType::MergeCandidate,
            'payload' => [
                'duplicate_entity_id' => 11,
            ],
            'reason' => 'Posibil duplicat.',
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $proposer->id,
        ]);

        $result = app(CurriculumProposalWorkflow::class)->markMerged(
            $proposal,
            $reviewer,
            'Confirmat duplicat.'
        );

        $this->assertSame(
            CurriculumProposalStatus::Merged,
            $result->status
        );
    }

    public function test_reviewed_proposal_cannot_be_reviewed_again(): void
    {
        $proposer = User::factory()->create();
        $reviewer = User::factory()->create();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'entity_id' => 10,
            'proposal_type' => CurriculumProposalType::Update,
            'payload' => [
                'name' => 'Matematică',
            ],
            'reason' => 'Corectare.',
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $proposer->id,
        ]);

        $workflow = app(CurriculumProposalWorkflow::class);

        $workflow->approve(
            $proposal,
            $reviewer
        );

        $this->expectException(DomainException::class);

        $workflow->reject(
            $proposal->refresh(),
            $reviewer
        );
    }
}
