<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_type',
    'entity_type',
    'entity_id',
    'proposal_id',
    'actor_id',
    'metadata',
    'occurred_at',
])]
class CurriculumAuditEvent extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(
            CurriculumProposal::class,
            'proposal_id'
        );
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'actor_id'
        );
    }
}
