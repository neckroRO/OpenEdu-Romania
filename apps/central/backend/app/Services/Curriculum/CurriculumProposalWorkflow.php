<?php

namespace App\Services\Curriculum;

use App\Enums\CurriculumProposalStatus;
use App\Models\CurriculumProposal;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CurriculumProposalWorkflow
{
    public function __construct(
        private readonly CurriculumAuditService $auditService
    ) {
    }

    public function approve(
        CurriculumProposal $proposal,
        User $reviewer,
        ?string $note = null
    ): CurriculumProposal {
        return $this->review(
            $proposal,
            $reviewer,
            CurriculumProposalStatus::Approved,
            $note
        );
    }

    public function reject(
        CurriculumProposal $proposal,
        User $reviewer,
        ?string $note = null
    ): CurriculumProposal {
        return $this->review(
            $proposal,
            $reviewer,
            CurriculumProposalStatus::Rejected,
            $note
        );
    }

    public function markMerged(
        CurriculumProposal $proposal,
        User $reviewer,
        ?string $note = null
    ): CurriculumProposal {
        return $this->review(
            $proposal,
            $reviewer,
            CurriculumProposalStatus::Merged,
            $note
        );
    }

    private function review(
        CurriculumProposal $proposal,
        User $reviewer,
        CurriculumProposalStatus $status,
        ?string $note
    ): CurriculumProposal {
        if ($proposal->status !== CurriculumProposalStatus::Pending) {
            throw new DomainException(
                'Only pending curriculum proposals can be reviewed.'
            );
        }

        return DB::transaction(function () use (
            $proposal,
            $reviewer,
            $status,
            $note
        ): CurriculumProposal {
            $previousStatus = $proposal->status;

            $proposal->update([
                'status' => $status,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            $eventType = match ($status) {
                CurriculumProposalStatus::Approved =>
                    'proposal_approved',
                CurriculumProposalStatus::Rejected =>
                    'proposal_rejected',
                CurriculumProposalStatus::Merged =>
                    'proposal_merged',
                default =>
                    'proposal_reviewed',
            };

            $this->auditService->record(
                eventType: $eventType,
                actor: $reviewer,
                proposal: $proposal,
                metadata: [
                    'from_status' => $previousStatus->value,
                    'to_status' => $status->value,
                    'review_note' => $note,
                ]
            );

            return $proposal->refresh();
        });
    }
}
