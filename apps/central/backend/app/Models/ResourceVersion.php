<?php

namespace App\Models;

use App\Enums\ResourceVersionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'resource_id',
    'version_number',
    'title',
    'summary',
    'content',
    'source_url',
    'language_code',
    'difficulty_level',
    'complexity_level',
    'status',
    'published_at',
    'created_by',
    'submitted_at',
    'reviewed_by',
    'reviewed_at',
    'review_note',
])]
class ResourceVersion extends Model
{
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
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
        'status' => ResourceVersionStatus::class,
        'published_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
	];
    }
}
