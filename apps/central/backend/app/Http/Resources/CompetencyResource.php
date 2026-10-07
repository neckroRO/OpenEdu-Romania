<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'display_order' => (int) $this->display_order,

            'concepts' => CompetencyConceptResource::collection(
                $this->whenLoaded('competencyConcepts')
            ),

            'children' => CompetencyResource::collection(
                $this->whenLoaded('children')
            ),
        ];
    }
}
