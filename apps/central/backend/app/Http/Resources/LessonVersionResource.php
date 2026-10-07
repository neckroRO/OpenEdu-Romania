<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'version_number' => (int) $this->version_number,
            'summary' => $this->summary,
            'learning_objectives' => $this->learning_objectives,
            'content' => $this->content,
            'estimated_duration_minutes' =>
                $this->estimated_duration_minutes === null
                    ? null
                    : (int) $this->estimated_duration_minutes,
            'language_code' => $this->language_code,
            'published_at' =>
                $this->published_at?->toISOString(),

            'resources' =>
                LessonVersionResourceLinkResource::collection(
                    $this->whenLoaded(
                        'lessonVersionResources'
                    )
                ),
        ];
    }
}