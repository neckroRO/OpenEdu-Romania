<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EditorialLessonResource;
use App\Models\Lesson;
use App\Models\LessonVersion;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EditorialLessonIndexController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize(
            'create',
            LessonVersion::class
        );

        $userId = $request->user()->id;

        $lessons = Lesson::query()
            ->whereHas(
                'versions',
                function ($query) use ($userId): void {
                    $query->where(
                        'created_by',
                        $userId
                    );
                }
            )
            ->with([
                'versions' => function (
                    $query
                ) use ($userId): void {
                    $query
                        ->where(
                            'created_by',
                            $userId
                        )
                        ->orderByDesc(
                            'version_number'
                        );
                },
            ])
            ->orderBy('curriculum_subject_id')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            EditorialLessonResource::collection(
                $lessons
            )->resolve()
        );
    }
}
