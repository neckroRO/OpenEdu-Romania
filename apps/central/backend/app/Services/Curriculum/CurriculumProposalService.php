<?php

namespace App\Services\Curriculum;

use App\Enums\CurriculumProposalStatus;
use App\Enums\CurriculumProposalType;
use App\Models\CurriculumProposal;
use App\Models\User;
use DomainException;

class CurriculumProposalService
{
    public function create(
        User $proposer,
        string $entityType,
        CurriculumProposalType $type,
        array $payload,
        ?int $entityId = null,
        ?string $reason = null
    ): CurriculumProposal {
        $this->validateEntityReference(
            $type,
            $entityId
        );

        if ($payload === []) {
            throw new DomainException(
                'Curriculum proposal payload cannot be empty.'
            );
        }

        return CurriculumProposal::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'proposal_type' => $type,
            'payload' => $payload,
            'reason' => $reason,
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $proposer->id,
        ]);
    }

    private function validateEntityReference(
        CurriculumProposalType $type,
        ?int $entityId
    ): void {
        if (
            $type === CurriculumProposalType::Create
            && $entityId !== null
        ) {
            throw new DomainException(
                'Create proposals cannot reference an existing entity.'
            );
        }

        if (
            $type !== CurriculumProposalType::Create
            && $entityId === null
        ) {
            throw new DomainException(
                'This proposal type requires an existing entity.'
            );
        }
    }
}
