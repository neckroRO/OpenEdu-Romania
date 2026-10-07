<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonCompetencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'display_order' => (int) $this->display_order,
            'is_core' => (bool) $this->is_core,

            'competency' => new CompetencyResource(
                $this->whenLoaded('competency')
            ),
        ];
    }
}
