<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'curriculum_version_id',
    'curriculum_framework_variant_id',
    'education_level_id',
    'subject_id',
    'curriculum_area_id',
    'component',
    'hours_min',
    'hours_max',
    'display_order',
    'status',
    'program_reference',
    'program_source_url',
    'program_approved_at',
])]
class CurriculumSubject extends Model
{
    public function curriculumVersion(): BelongsTo
    {
        return $this->belongsTo(CurriculumVersion::class);
    }

    public function curriculumFrameworkVariant(): BelongsTo
    {
        return $this->belongsTo(CurriculumFrameworkVariant::class);
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function curriculumArea(): BelongsTo
    {
        return $this->belongsTo(CurriculumArea::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function competencies(): HasMany
    {
        return $this->hasMany(Competency::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    protected function casts(): array
    {
        return [
            'hours_min' => 'integer',
            'hours_max' => 'integer',
            'program_approved_at' => 'date',
        ];
    }
}
