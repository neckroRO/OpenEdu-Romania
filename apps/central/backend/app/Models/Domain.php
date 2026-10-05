<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'curriculum_subject_id',
    'parent_domain_id',
    'title',
    'description',
    'display_order',
])]
class Domain extends Model
{
    public function curriculumSubject(): BelongsTo
    {
        return $this->belongsTo(CurriculumSubject::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            Domain::class,
            'parent_domain_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            Domain::class,
            'parent_domain_id'
        );
    }

    public function conceptPlacements(): HasMany
    {
        return $this->hasMany(ConceptPlacement::class);
    }

    public function concepts(): BelongsToMany
    {
        return $this->belongsToMany(
            Concept::class,
            'concept_placements'
        )
            ->withPivot(['display_order', 'is_core'])
            ->withTimestamps();
    }
}
