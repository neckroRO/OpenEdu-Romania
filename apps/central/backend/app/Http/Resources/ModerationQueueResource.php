<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModerationQueueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $version = $this->resource;

        $educationalResource = $version->getRelation(
            'resource'
        );

        $conceptLinks = $educationalResource
            ->resourceConcepts;

        $conceptLink = $conceptLinks
            ->firstWhere('is_primary', true)
            ?? $conceptLinks->first();

        $concept = $conceptLink?->concept;

        return [
            'id' => (int) $version->id,

            'resource' => [
                'id' => (int) $educationalResource->id,
                'code' => $educationalResource->code,
                'type' => $educationalResource->type,
                'status' => $educationalResource->status,
            ],

            'concept' => $concept
                ? [
                    'id' => (int) $concept->id,
                    'code' => $concept->code,
                    'title' => $concept->title,
                ]
                : null,

            'creator' => $version->creator
                ? [
                    'id' => (int) $version->creator->id,
                    'name' => $version->creator->name,
                    'email' => $version->creator->email,
                ]
                : null,

            'version_number' =>
                (int) $version->version_number,

            'title' => $version->title,
            'summary' => $version->summary,
            'content' => $version->content,
            'source_url' => $version->source_url,
            'language_code' => $version->language_code,

            'difficulty_level' =>
                (int) $version->difficulty_level,

            'complexity_level' =>
                (int) $version->complexity_level,

            'status' => $version->status->value,

            'submitted_at' =>
                $version->submitted_at?->toISOString(),

            'reviewed_by' => $version->reviewed_by,

            'reviewed_at' =>
                $version->reviewed_at?->toISOString(),

            'review_note' => $version->review_note,

            'published_at' =>
                $version->published_at?->toISOString(),
        ];
    }
}
