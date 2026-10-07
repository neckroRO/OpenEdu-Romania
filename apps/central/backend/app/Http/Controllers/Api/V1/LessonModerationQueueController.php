<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LessonVersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\LessonModerationQueueResource;
use App\Models\LessonVersion;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LessonModerationQueueController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize(
            'viewAny',
            LessonVersion::class
        );

        $versions = LessonVersion::query()
            ->whereIn('status', [
                LessonVersionStatus::Submitted->value,
                LessonVersionStatus::Approved->value,
            ])
            ->with([
                'creator',
                'lesson',
            ])
            ->orderByRaw(
                "CASE WHEN status = 'submitted' THEN 0 ELSE 1 END"
            )
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            LessonModerationQueueResource::collection(
                $versions
            )->resolve()
        );
    }
}
