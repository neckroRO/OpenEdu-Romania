<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurriculumSubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'display_order' => (int) $this->display_order,

            'program' => [
                'reference' => $this->program_reference,
                'source_url' => $this->program_source_url,
                'approved_at' => $this->program_approved_at
                    ? $this->program_approved_at->toDateString()
                    : null,
            ],

            'subject' => new SubjectResource(
                $this->whenLoaded('subject')
            ),
        ];
    }
}
