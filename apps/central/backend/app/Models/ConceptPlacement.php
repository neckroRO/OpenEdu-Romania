<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'concept_id',
    'domain_id',
    'display_order',
    'is_core',
])]
class ConceptPlacement extends Model
{
    public function concept(): BelongsTo
    {
        return $this->belongsTo(Concept::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
        ];
    }
}
