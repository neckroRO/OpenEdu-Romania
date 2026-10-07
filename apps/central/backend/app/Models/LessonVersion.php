<?php

namespace App\Models;

use App\Enums\LessonVersionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'lesson_id',
    'version_number',
    'summary',
    'learning_objectives',
    'content',
    'estimated_duration_minutes',
    'language_code',
    'status',
    'review_round',
    'published_at',
    'created_by',
    'submitted_at',
    'reviewed_by',
    'reviewed_at',
    'review_note',
])]
class LessonVersion extends Model
{
    public function lessonVersionResources(): HasMany
    {
        return $this->hasMany(
            LessonVersionResource::class
        );
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(
            Resource::class,
            'lesson_version_resources'
        )
            ->withPivot([
                'role',
                'display_order',
                'is_required',
            ])
            ->withTimestamps()
            ->orderByPivot('display_order');
    }

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

    public function reviews(): HasMany
    {
        return $this->hasMany(LessonVersionReview::class);
    }

    public function reputationEvents(): HasMany
    {
        return $this->hasMany(ReputationEvent::class);
    }

    protected function casts(): array
    {
        return [
            'learning_objectives' => 'array',
            'status' => LessonVersionStatus::class,
            'review_round' => 'integer',
            'published_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
