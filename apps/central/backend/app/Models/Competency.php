<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'curriculum_subject_id',
    'parent_competency_id',
    'code',
    'type',
    'title',
    'description',
    'display_order',
    'status',
])]
class Competency extends Model
{
    public function curriculumSubject(): BelongsTo
    {
        return $this->belongsTo(CurriculumSubject::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            Competency::class,
            'parent_competency_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            Competency::class,
            'parent_competency_id'
        );
    }

    public function competencyConcepts(): HasMany
    {
        return $this->hasMany(CompetencyConcept::class);
    }

    public function concepts(): BelongsToMany
    {
        return $this->belongsToMany(
            Concept::class,
            'competency_concepts'
        )
            ->withPivot(['display_order', 'is_core'])
            ->withTimestamps();
    }
}
