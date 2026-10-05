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

            'subject' => new SubjectResource(
                $this->whenLoaded('subject')
            ),
        ];
    }
}
