<?php

namespace App\Models;

use App\Enums\LessonVersionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lesson_id',
    'version_number',
    'summary',
    'learning_objectives',
    'content',
    'estimated_duration_minutes',
    'language_code',
    'status',
    'published_at',
    'created_by',
    'submitted_at',
    'reviewed_by',
    'reviewed_at',
    'review_note',
])]
class LessonVersion extends Model
{
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected function casts(): array
    {
        return [
            'learning_objectives' => 'array',
            'status' => LessonVersionStatus::class,
            'published_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
