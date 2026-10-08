<?php

namespace Tests\Feature;

use App\Enums\CurriculumProposalStatus;
use App\Enums\CurriculumProposalType;
use App\Models\CurriculumProposal;
use App\Models\Subject;
use App\Models\User;
use App\Services\Curriculum\CurriculumMergeModerationService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumMergeModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_merge_candidate_can_be_previewed(): void
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

        $user = User::factory()->create();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'entity_id' => $source->id,
            'proposal_type' =>
                CurriculumProposalType::MergeCandidate,
            'payload' => [
                'duplicate_entity_id' => $target->id,
            ],
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $user->id,
        ]);

        $preview = app(
            CurriculumMergeModerationService::class
        )->preview($proposal);

        $this->assertSame(
            $source->id,
            $preview->sourceSubjectId
        );

        $this->assertSame(
            $target->id,
            $preview->targetSubjectId
        );

        $this->assertFalse($preview->blocked);
    }

    public function test_merge_candidate_can_be_confirmed(): void
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
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $proposer->id,
        ]);

        $merge = app(
            CurriculumMergeModerationService::class
        )->confirm(
            $proposal,
            $moderator,
            'Duplicate confirmat.'
        );

        $this->assertSame(
            $source->id,
            $merge->source_entity_id
        );

        $this->assertSame(
            $target->id,
            $merge->target_entity_id
        );

        $this->assertSame(
            CurriculumProposalStatus::Merged,
            $proposal->refresh()->status
        );

        $this->assertSame(
            'merged',
            $source->refresh()->status
        );
    }

    public function test_non_merge_proposal_cannot_be_previewed(): void
    {
        $subject = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        $user = User::factory()->create();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'entity_id' => $subject->id,
            'proposal_type' => CurriculumProposalType::Update,
            'payload' => [
                'name' => 'Mate',
            ],
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $user->id,
        ]);

        $this->expectException(DomainException::class);

        app(
            CurriculumMergeModerationService::class
        )->preview($proposal);
    }

    public function test_non_pending_merge_proposal_cannot_be_confirmed(): void
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

        $user = User::factory()->create();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'entity_id' => $source->id,
            'proposal_type' =>
                CurriculumProposalType::MergeCandidate,
            'payload' => [
                'duplicate_entity_id' => $target->id,
            ],
            'status' => CurriculumProposalStatus::Rejected,
            'proposed_by' => $user->id,
        ]);

        $this->expectException(DomainException::class);

        app(
            CurriculumMergeModerationService::class
        )->confirm(
            $proposal,
            $user
        );
    }

    public function test_unknown_target_subject_is_rejected(): void
    {
        $source = Subject::create([
            'code' => 'math-community',
            'name' => 'Mate',
            'status' => 'active',
        ]);

        $user = User::factory()->create();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'entity_id' => $source->id,
            'proposal_type' =>
                CurriculumProposalType::MergeCandidate,
            'payload' => [
                'duplicate_entity_id' => 999999,
            ],
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $user->id,
        ]);

        $this->expectException(DomainException::class);

        app(
            CurriculumMergeModerationService::class
        )->preview($proposal);
    }

    public function test_blocked_preview_prevents_merge(): void
    {
        $subject = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        $user = User::factory()->create();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'entity_id' => $subject->id,
            'proposal_type' =>
                CurriculumProposalType::MergeCandidate,
            'payload' => [
                'duplicate_entity_id' => $subject->id,
            ],
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $user->id,
        ]);

        try {
            app(
                CurriculumMergeModerationService::class
            )->confirm(
                $proposal,
                $user
            );

            $this->fail(
                'Expected blocked merge was not rejected.'
            );
        } catch (DomainException $exception) {
            $this->assertSame(
                'The proposed merge is blocked by preview validation.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            CurriculumProposalStatus::Pending,
            $proposal->refresh()->status
        );

        $this->assertSame(
            'active',
            $subject->refresh()->status
        );

        $this->assertDatabaseCount(
            'curriculum_entity_merges',
            0
        );
    }
}
