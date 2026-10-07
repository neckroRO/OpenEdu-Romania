<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lesson_id',
    'concept_id',
    'display_order',
    'is_core',
])]
class LessonConcept extends Model
{
    protected static function booted(): void
    {
        static::saving(function (LessonConcept $link): void {
            $lesson = Lesson::findOrFail(
                $link->lesson_id
            );

            $concept = Concept::findOrFail(
                $link->concept_id
            );

            $belongsToCurriculumSubject = $concept
                ->domains()
                ->where(
                    'curriculum_subject_id',
                    $lesson->curriculum_subject_id
                )
                ->exists();

            if (! $belongsToCurriculumSubject) {
                throw new DomainException(
                    'Lesson and concept must belong to the same curriculum subject.'
                );
            }
        });
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function concept(): BelongsTo
    {
        return $this->belongsTo(Concept::class);
    }

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
        ];
    }
}
