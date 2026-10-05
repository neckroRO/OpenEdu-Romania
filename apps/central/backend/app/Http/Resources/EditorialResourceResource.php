<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EditorialResourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $version = $this->versions->first();

        $conceptLink = $this->resourceConcepts
            ->firstWhere('is_primary', true)
            ?? $this->resourceConcepts->first();

        $concept = $conceptLink?->concept;

        return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'status' => $this->status,

            'concept' => $concept
                ? [
                    'id' => (int) $concept->id,
                    'code' => $concept->code,
                    'title' => $concept->title,
                ]
                : null,

            'version' => $version
                ? [
                    'id' => (int) $version->id,
                    'resource_id' => (int) $version->resource_id,
                    'version_number' =>
                        (int) $version->version_number,
                    'title' => $version->title,
                    'summary' => $version->summary,
                    'content' => $version->content,
                    'source_url' => $version->source_url,
                    'language_code' =>
                        $version->language_code,
                    'difficulty_level' =>
                        (int) $version->difficulty_level,
                    'complexity_level' =>
                        (int) $version->complexity_level,
                    'status' => $version->status->value,
                    'submitted_at' =>
                        $version->submitted_at?->toISOString(),
                    'reviewed_by' =>
                        $version->reviewed_by,
                    'reviewed_at' =>
                        $version->reviewed_at?->toISOString(),
                    'review_note' =>
                        $version->review_note,
                    'published_at' =>
                        $version->published_at?->toISOString(),
                ]
                : null,
        ];
    }
}
