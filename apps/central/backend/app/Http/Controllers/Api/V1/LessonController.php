<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonDetailResource;
use App\Models\Lesson;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class LessonController extends Controller
{

public function show(Lesson $lesson): JsonResponse
{
    $lesson = Lesson::query()
        ->with([
            'versions' => function ($query) {
                $query
                    ->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->with([
                        'lessonVersionResources' => function ($query) {
                            $query
                                ->whereHas(
                                    'resource',
                                    function ($resourceQuery) {
                                        $resourceQuery
                                            ->where('status', 'active');
                                    }
                                )
                                ->with('resource')
                                ->orderBy('display_order')
                                ->orderBy('id');
                        },
                    ])
                    ->orderByDesc('version_number');
            },

            'lessonCompetencies' => function ($query) {
                $query
                    ->whereHas(
                        'competency',
                        function ($competencyQuery) {
                            $competencyQuery
                                ->where('status', 'active');
                        }
                    )
                    ->with('competency')
                    ->orderBy('display_order')
                    ->orderBy('id');
            },

            'lessonConcepts' => function ($query) {
                $query
                    ->whereHas(
                        'concept',
                        function ($conceptQuery) {
                            $conceptQuery
                                ->where('status', 'active');
                        }
                    )
                    ->with('concept')
                    ->orderBy('display_order')
                    ->orderBy('id');
            },
        ])
        ->whereKey($lesson->id)
        ->where('status', 'active')
        ->whereHas('versions', function ($query) {
            $query
                ->where('status', 'published')
                ->whereNotNull('published_at');
        })
        ->first();

    if ($lesson === null) {
        abort(404);
    }

    return ApiResponse::success(
        (new LessonDetailResource(
            $lesson
        ))->resolve()
    );
}

}
