<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurriculumProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id === null
                ? null
                : (int) $this->entity_id,
            'proposal_type' => $this->proposal_type->value,
            'payload' => $this->payload,
            'reason' => $this->reason,
            'status' => $this->status->value,
            'proposed_by' => (int) $this->proposed_by,
            'reviewed_by' => $this->reviewed_by === null
                ? null
                : (int) $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'review_note' => $this->review_note,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
