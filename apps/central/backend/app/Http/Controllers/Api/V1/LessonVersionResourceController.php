<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LessonVersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLessonVersionResourcesRequest;
use App\Http\Resources\LessonVersionResourceLinkResource;
use App\Models\LessonVersion;
use App\Models\LessonVersionResource;
use App\Models\Resource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LessonVersionResourceController extends Controller
{
    public function update(
        UpdateLessonVersionResourcesRequest $request,
        LessonVersion $lessonVersion
    ): JsonResponse {
        Gate::authorize(
            'update',
            $lessonVersion
        );

        if (
            $lessonVersion->status
            !== LessonVersionStatus::Draft
        ) {
            return ApiResponse::error(
                code: 'LESSON_VERSION_NOT_EDITABLE',
                message:
                    'Resursele pot fi modificate doar pentru o versiune draft.',
                status: 409,
            );
        }

        $validated = $request->validated();

        $resourceIds = collect(
            $validated['resources']
        )
            ->pluck('resource_id')
            ->values();

        if ($resourceIds->isNotEmpty()) {
            $activeResourceCount = Resource::query()
                ->whereIn('id', $resourceIds)
                ->where('status', 'active')
                ->count();

            if (
                $activeResourceCount
                !== $resourceIds->count()
            ) {
                return ApiResponse::error(
                    code: 'LESSON_RESOURCE_NOT_ACTIVE',
                    message:
                        'Toate resursele asociate trebuie să fie active.',
                    status: 422,
                );
            }
        }

        DB::transaction(
            function () use (
                $lessonVersion,
                $validated
            ): void {
                LessonVersionResource::query()
                    ->where(
                        'lesson_version_id',
                        $lessonVersion->id
                    )
                    ->delete();

                foreach (
                    $validated['resources']
                    as $resource
                ) {
                    LessonVersionResource::create([
                        'lesson_version_id' =>
                            $lessonVersion->id,
                        'resource_id' =>
                            $resource['resource_id'],
                        'role' =>
                            $resource['role'],
                        'display_order' =>
                            $resource['display_order'],
                        'is_required' =>
                            $resource['is_required'],
                    ]);
                }
            }
        );

        $links = $lessonVersion
            ->lessonVersionResources()
            ->with('resource')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            LessonVersionResourceLinkResource::collection(
                $links
            )->resolve()
        );
    }
}
