<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'resource_id',
    'concept_id',
    'is_primary',
    'display_order',
])]
class ResourceConcept extends Model
{
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function concept(): BelongsTo
    {
        return $this->belongsTo(Concept::class);
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }
}
