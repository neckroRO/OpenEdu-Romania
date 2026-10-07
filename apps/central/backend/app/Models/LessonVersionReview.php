<?php

namespace App\Models;

use App\Enums\LessonReviewVerdict;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'lesson_version_id',
    'reviewer_id',
    'review_round',
    'verdict',
    'correctness_score',
    'curriculum_alignment_score',
    'clarity_score',
    'pedagogical_value_score',
    'difficulty_fit_score',
    'comment',
    'weight_snapshot',
])]
class LessonVersionReview extends Model
{
    public function lessonVersion(): BelongsTo
    {
        return $this->belongsTo(LessonVersion::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reputationEvents(): HasMany
    {
        return $this->hasMany(ReputationEvent::class);
    }

    protected function casts(): array
    {
        return [
            'verdict' => LessonReviewVerdict::class,
            'review_round' => 'integer',
            'correctness_score' => 'integer',
            'curriculum_alignment_score' => 'integer',
            'clarity_score' => 'integer',
            'pedagogical_value_score' => 'integer',
            'difficulty_fit_score' => 'integer',
            'weight_snapshot' => 'decimal:2',
        ];
    }
}
