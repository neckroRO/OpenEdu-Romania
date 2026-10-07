<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'curriculum_subject_id',
    'code',
    'title',
    'description',
    'display_order',
    'status',
])]
class Lesson extends Model
{
    public function curriculumSubject(): BelongsTo
    {
        return $this->belongsTo(CurriculumSubject::class);
    }

    public function lessonConcepts(): HasMany
    {
        return $this->hasMany(LessonConcept::class);
    }

    public function concepts(): BelongsToMany
    {
        return $this->belongsToMany(
            Concept::class,
            'lesson_concepts'
        )
            ->withPivot(['display_order', 'is_core'])
            ->withTimestamps()
            ->orderByPivot('display_order');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LessonVersion::class);
    }

    public function lessonCompetencies(): HasMany
    {
        return $this->hasMany(LessonCompetency::class);
    }

    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Competency::class,
            'lesson_competencies'
        )
            ->withPivot(['display_order', 'is_core'])
            ->withTimestamps();
    }
}
