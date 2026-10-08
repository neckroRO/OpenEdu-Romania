<?php

namespace App\Services\Curriculum;

use App\Enums\CurriculumProposalStatus;
use App\Models\CurriculumProposal;
use App\Models\User;
use DomainException;

class CurriculumProposalWorkflow
{
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

        $proposal->update([
            'status' => $status,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);

        return $proposal->refresh();
    }
}
