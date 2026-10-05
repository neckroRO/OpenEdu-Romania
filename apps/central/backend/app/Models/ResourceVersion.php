<?php

namespace App\Models;

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
])]
class ResourceVersion extends Model
{
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
