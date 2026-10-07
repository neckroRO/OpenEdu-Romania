<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EditorialLessonVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'lesson_id' => (int) $this->lesson_id,
            'version_number' => (int) $this->version_number,

            'summary' => $this->summary,
            'learning_objectives' => $this->learning_objectives,
            'content' => $this->content,

            'estimated_duration_minutes' =>
                $this->estimated_duration_minutes === null
                    ? null
                    : (int) $this->estimated_duration_minutes,

            'language_code' => $this->language_code,

            'status' => $this->status->value,

            'created_by' => $this->created_by,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'review_note' => $this->review_note,
            'published_at' => $this->published_at?->toISOString(),
        ];
    }
}
