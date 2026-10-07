<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $publishedVersion = $this->versions->first();

        return [
            'id' => (int) $this->id,
            'curriculum_subject_id' =>
                (int) $this->curriculum_subject_id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,

            'version' => $publishedVersion
                ? new LessonVersionResource(
                    $publishedVersion
                )
                : null,

            'competencies' =>
                LessonCompetencyResource::collection(
                    $this->whenLoaded(
                        'lessonCompetencies'
                    )
                ),

            'concepts' =>
                LessonConceptResource::collection(
                    $this->whenLoaded(
                        'lessonConcepts'
                    )
                ),
        ];
    }
}