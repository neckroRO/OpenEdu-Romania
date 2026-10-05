<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EditorialResourceVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'resource_id' => (int) $this->resource_id,
            'version_number' => (int) $this->version_number,
            'title' => $this->title,
            'status' => $this->status->value,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'review_note' => $this->review_note,
            'published_at' => $this->published_at?->toISOString(),
        ];
    }
}
