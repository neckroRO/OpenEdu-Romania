<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'curriculum_version_id',
    'code',
    'name',
    'source_reference',
    'source_url',
    'source_annex',
    'approved_at',
    'status',
])]
class CurriculumFrameworkVariant extends Model
{
    public function curriculumVersion(): BelongsTo
    {
        return $this->belongsTo(CurriculumVersion::class);
    }

    public function curriculumSubjects(): HasMany
    {
        return $this->hasMany(CurriculumSubject::class);
    }

    protected function casts(): array
    {
        return [
            'approved_at' => 'date',
        ];
    }
}
