<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'title',
    'description',
    'status',
])]
class Concept extends Model
{
    public function placements(): HasMany
    {
        return $this->hasMany(ConceptPlacement::class);
    }

    public function domains(): BelongsToMany
    {
        return $this->belongsToMany(
            Domain::class,
            'concept_placements'
        )
            ->withPivot(['display_order', 'is_core'])
            ->withTimestamps();
    }

    public function competencyConcepts(): HasMany
    {
        return $this->hasMany(CompetencyConcept::class);
    }

    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Competency::class,
            'competency_concepts'
        )
            ->withPivot(['display_order', 'is_core'])
            ->withTimestamps();
    }

    public function resourceConcepts(): HasMany
    {
        return $this->hasMany(ResourceConcept::class);
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(
            Resource::class,
            'resource_concepts'
        )
            ->withPivot(['is_primary', 'display_order'])
            ->withTimestamps();
    }
}
