<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'entity_type',
    'source_entity_id',
    'target_entity_id',
    'merged_by',
    'merged_at',
    'reason',
    'metadata',
])]
class CurriculumEntityMerge extends Model
{
    public function merger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by');
    }

    protected function casts(): array
    {
        return [
            'merged_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
