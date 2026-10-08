<?php

namespace App\Services\Curriculum;

use App\Models\CurriculumAuditEvent;
use App\Models\CurriculumProposal;
use App\Models\User;

class CurriculumAuditService
{
    public function record(
        string $eventType,
        ?User $actor = null,
        ?CurriculumProposal $proposal = null,
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = []
    ): CurriculumAuditEvent {
        return CurriculumAuditEvent::create([
            'event_type' => $eventType,
            'entity_type' =>
                $entityType ?? $proposal?->entity_type,
            'entity_id' =>
                $entityId ?? $proposal?->entity_id,
            'proposal_id' => $proposal?->id,
            'actor_id' => $actor?->id,
            'metadata' => $metadata === []
                ? null
                : $metadata,
            'occurred_at' => now(),
        ]);
    }
}
