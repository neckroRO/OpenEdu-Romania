<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lesson_id',
    'competency_id',
    'display_order',
    'is_core',
])]
class LessonCompetency extends Model
{
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    protected static function booted(): void
    {
        static::saving(function (LessonCompetency $link): void {
            $lessonCurriculumSubjectId = Lesson::query()
                ->whereKey($link->lesson_id)
                ->value('curriculum_subject_id');

            $competencyCurriculumSubjectId = Competency::query()
                ->whereKey($link->competency_id)
                ->value('curriculum_subject_id');

            if (
                $lessonCurriculumSubjectId !== null
                && $competencyCurriculumSubjectId !== null
                && (int) $lessonCurriculumSubjectId !== (int) $competencyCurriculumSubjectId
            ) {
                throw new DomainException(
                    'Lesson and competency must belong to the same curriculum subject.'
                );
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
        ];
    }
}
