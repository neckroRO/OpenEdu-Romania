<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLessonConceptsRequest;
use App\Http\Resources\LessonConceptResource;
use App\Models\Concept;
use App\Models\Lesson;
use App\Models\LessonConcept;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LessonConceptController extends Controller
{
    public function update(
        UpdateLessonConceptsRequest $request,
        Lesson $lesson
    ): JsonResponse {
        Gate::authorize(
            'updateConcepts',
            $lesson
        );

        $validated = $request->validated();

        $conceptIds = collect(
            $validated['concepts']
        )
            ->pluck('concept_id')
            ->values();

        if ($conceptIds->isNotEmpty()) {
            $validConceptCount = Concept::query()
                ->whereIn('id', $conceptIds)
                ->whereHas(
                    'domains',
                    function ($query) use ($lesson): void {
                        $query->where(
                            'curriculum_subject_id',
                            $lesson->curriculum_subject_id
                        );
                    }
                )
                ->count();

            if ($validConceptCount !== $conceptIds->count()) {
                return ApiResponse::error(
                    code: 'LESSON_CONCEPT_OUT_OF_SCOPE',
                    message:
                        'Toate conceptele trebuie să aparțină aceleiași discipline curriculare ca lecția.',
                    status: 422,
                );
            }
        }

        DB::transaction(
            function () use ($lesson, $validated): void {
                LessonConcept::query()
                    ->where(
                        'lesson_id',
                        $lesson->id
                    )
                    ->delete();

                foreach (
                    $validated['concepts']
                    as $concept
                ) {
                    LessonConcept::create([
                        'lesson_id' => $lesson->id,
                        'concept_id' =>
                            $concept['concept_id'],
                        'display_order' =>
                            $concept['display_order'],
                        'is_core' =>
                            $concept['is_core'],
                    ]);
                }
            }
        );

        $links = $lesson
            ->lessonConcepts()
            ->with('concept')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            LessonConceptResource::collection(
                $links
            )->resolve()
        );
    }
}
