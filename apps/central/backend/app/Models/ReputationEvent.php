<?php

namespace App\Models;

use App\Enums\ReputationEventType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'subject_id',
    'event_type',
    'event_key',
    'points',
    'lesson_version_id',
    'lesson_version_review_id',
    'metadata',
])]
class ReputationEvent extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function lessonVersion(): BelongsTo
    {
        return $this->belongsTo(LessonVersion::class);
    }

    public function lessonVersionReview(): BelongsTo
    {
        return $this->belongsTo(LessonVersionReview::class);
    }

    protected function casts(): array
    {
        return [
            'event_type' => ReputationEventType::class,
            'points' => 'integer',
            'metadata' => 'array',
        ];
    }
}
