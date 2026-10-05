<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurriculumSubjectResource;
use App\Models\CurriculumSubject;
use App\Models\EducationLevel;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class EducationLevelSubjectController extends Controller
{
    public function index(EducationLevel $educationLevel): JsonResponse
    {
        $subjects = CurriculumSubject::query()
            ->with('subject')
            ->where('education_level_id', $educationLevel->id)
            ->where('status', 'active')
            ->whereHas('subject', function ($query) {
                $query->where('status', 'active');
            })
            ->whereHas('curriculumVersion', function ($query) {
                $query
                    ->where('status', 'active')
                    ->whereHas('curriculum', function ($query) {
                        $query->where('status', 'active');
                    });
            })
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            CurriculumSubjectResource::collection($subjects)->resolve()
        );
    }
}
