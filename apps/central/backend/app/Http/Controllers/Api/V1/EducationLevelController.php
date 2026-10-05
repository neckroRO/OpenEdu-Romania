<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EducationLevelResource;
use App\Models\EducationLevel;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class EducationLevelController extends Controller
{
    public function index(): JsonResponse
    {
        $levels = EducationLevel::query()
            ->orderBy('ordinal')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            EducationLevelResource::collection($levels)->resolve()
        );
    }
}
