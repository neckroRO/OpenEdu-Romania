<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EditorialLessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $version = $this->versions->first();

        return [
            'id' => (int) $this->id,
            'curriculum_subject_id' =>
                (int) $this->curriculum_subject_id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,

            'version' => $version
                ? [
                    'id' => (int) $version->id,
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
                    'language_code' =>
                        $version->language_code,
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
