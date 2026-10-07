<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'display_order' => (int) $this->display_order,

            'competencies' => LessonCompetencyResource::collection(
                $this->whenLoaded('lessonCompetencies')
            ),
        ];
    }
}
