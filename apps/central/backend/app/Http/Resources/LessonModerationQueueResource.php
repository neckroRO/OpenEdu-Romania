<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonModerationQueueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $version = $this->resource;
        $lesson = $version->lesson;

        return [
            'id' => (int) $version->id,

            'lesson' => [
                'id' => (int) $lesson->id,
                'curriculum_subject_id' =>
                    (int) $lesson->curriculum_subject_id,
                'code' => $lesson->code,
                'title' => $lesson->title,
                'status' => $lesson->status,
            ],

            'creator' => $version->creator
                ? [
                    'id' => (int) $version->creator->id,
                    'name' => $version->creator->name,
                    'email' => $version->creator->email,
                ]
                : null,

            'version_number' =>
                (int) $version->version_number,

            'summary' => $version->summary,
            'learning_objectives' =>
                $version->learning_objectives,
            'content' => $version->content,

            'estimated_duration_minutes' =>
                $version->estimated_duration_minutes === null
                    ? null
                    : (int) $version
                        ->estimated_duration_minutes,

            'language_code' => $version->language_code,
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
