<?php

namespace App\Models;

use App\Enums\CurriculumProposalStatus;
use App\Enums\CurriculumProposalType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'entity_type',
    'entity_id',
    'proposal_type',
    'payload',
    'reason',
    'status',
    'proposed_by',
    'reviewed_by',
    'reviewed_at',
    'review_note',
])]
class CurriculumProposal extends Model
{
    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected function casts(): array
    {
        return [
            'proposal_type' => CurriculumProposalType::class,
            'status' => CurriculumProposalStatus::class,
            'payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }
}
