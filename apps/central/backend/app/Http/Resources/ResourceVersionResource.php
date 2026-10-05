<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourceVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'version_number' => (int) $this->version_number,
            'title' => $this->title,
            'summary' => $this->summary,
            'content' => $this->content,
            'source_url' => $this->source_url,
            'language_code' => $this->language_code,
            'difficulty_level' => (int) $this->difficulty_level,
            'complexity_level' => (int) $this->complexity_level,
            'published_at' => $this->published_at?->toISOString(),
        ];
    }
}
