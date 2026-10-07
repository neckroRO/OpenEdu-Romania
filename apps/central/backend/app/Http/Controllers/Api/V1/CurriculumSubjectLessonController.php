<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonResource;
use App\Models\CurriculumSubject;
use App\Models\Lesson;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CurriculumSubjectLessonController extends Controller
{
    public function index(
        CurriculumSubject $curriculumSubject
    ): JsonResponse {
        $lessons = Lesson::query()
            ->where(
                'curriculum_subject_id',
                $curriculumSubject->id
            )
            ->where('status', 'active')
            ->with([
                'lessonCompetencies' => function ($query) {
                    $query
                        ->whereHas('competency', function ($competencyQuery) {
                            $competencyQuery->where('status', 'active');
                        })
                        ->with('competency')
                        ->orderBy('display_order')
                        ->orderBy('id');
                },
            ])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            LessonResource::collection($lessons)->resolve()
        );
    }
}
