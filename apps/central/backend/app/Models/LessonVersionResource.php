<?php

namespace App\Models;

use App\Enums\LessonResourceRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lesson_version_id',
    'resource_id',
    'role',
    'display_order',
    'is_required',
])]
class LessonVersionResource extends Model
{
    public function lessonVersion(): BelongsTo
    {
        return $this->belongsTo(
            LessonVersion::class
        );
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    protected function casts(): array
    {
        return [
            'role' => LessonResourceRole::class,
            'is_required' => 'boolean',
        ];
    }
}
