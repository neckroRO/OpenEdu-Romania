<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'competency_id',
    'concept_id',
    'display_order',
    'is_core',
])]
class CompetencyConcept extends Model
{
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    public function concept(): BelongsTo
    {
        return $this->belongsTo(Concept::class);
    }

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
        ];
    }
}
