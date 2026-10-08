<?php

namespace App\Services\Curriculum;

use App\Data\Curriculum\SubjectMergePreview;
use App\Enums\CurriculumProposalStatus;
use App\Enums\CurriculumProposalType;
use App\Models\CurriculumEntityMerge;
use App\Models\CurriculumProposal;
use App\Models\Subject;
use App\Models\User;
use DomainException;

class CurriculumMergeModerationService
{
    public function __construct(
        private readonly SubjectMergePreviewService $previewService,
        private readonly SubjectMergeService $mergeService,
        private readonly CurriculumProposalWorkflow $proposalWorkflow,
    ) {
    }

    public function preview(
        CurriculumProposal $proposal
    ): SubjectMergePreview {
        $this->assertMergeCandidate($proposal);

        [$source, $target] = $this->resolveSubjects($proposal);

        return $this->previewService->preview(
            $source,
            $target
        );
    }

    public function confirm(
        CurriculumProposal $proposal,
        User $moderator,
        ?string $note = null
    ): CurriculumEntityMerge {
        $this->assertMergeCandidate($proposal);

        if (
            $proposal->status !== CurriculumProposalStatus::Pending
        ) {
            throw new DomainException(
                'Only pending merge proposals can be confirmed.'
            );
        }

        [$source, $target] = $this->resolveSubjects($proposal);

        $preview = $this->previewService->preview(
            $source,
            $target
        );

        if ($preview->blocked) {
            throw new DomainException(
                'The proposed merge is blocked by preview validation.'
            );
        }

        $merge = $this->mergeService->merge(
            $source,
            $target,
            $moderator,
            $note ?? $proposal->reason
        );

        $this->proposalWorkflow->markMerged(
            $proposal->refresh(),
            $moderator,
            $note
        );

        return $merge;
    }

    private function assertMergeCandidate(
        CurriculumProposal $proposal
    ): void {
        if (
            $proposal->proposal_type
            !== CurriculumProposalType::MergeCandidate
        ) {
            throw new DomainException(
                'The proposal is not a merge candidate.'
            );
        }

        if ($proposal->entity_type !== 'subject') {
            throw new DomainException(
                'Only subject merge proposals are currently supported.'
            );
        }
    }

    private function resolveSubjects(
        CurriculumProposal $proposal
    ): array {
        if ($proposal->entity_id === null) {
            throw new DomainException(
                'Merge proposal source entity is missing.'
            );
        }

        $targetId = $proposal->payload['duplicate_entity_id']
            ?? null;

        if (! is_int($targetId)) {
            throw new DomainException(
                'Merge proposal target entity is missing.'
            );
        }

        $source = Subject::find($proposal->entity_id);
        $target = Subject::find($targetId);

        if ($source === null || $target === null) {
            throw new DomainException(
                'Merge proposal references an unknown subject.'
            );
        }

        return [$source, $target];
    }
}
