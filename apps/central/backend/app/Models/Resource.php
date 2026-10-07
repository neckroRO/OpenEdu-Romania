<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'type',
    'status',
])]
class Resource extends Model
{
    public function versions(): HasMany
    {
        return $this->hasMany(ResourceVersion::class);
    }

    public function lessonVersionResources(): HasMany
    {
        return $this->hasMany(
            LessonVersionResource::class
        );
    }

    public function lessonVersions(): BelongsToMany
    {
        return $this->belongsToMany(
            LessonVersion::class,
            'lesson_version_resources'
        )
            ->withPivot([
                'role',
                'display_order',
                'is_required',
            ])
            ->withTimestamps();
    }

    public function resourceConcepts(): HasMany
    {
        return $this->hasMany(ResourceConcept::class);
    }

    public function concepts(): BelongsToMany
    {
        return $this->belongsToMany(
            Concept::class,
            'resource_concepts'
        )
            ->withPivot(['is_primary', 'display_order'])
            ->withTimestamps();
    }
}
