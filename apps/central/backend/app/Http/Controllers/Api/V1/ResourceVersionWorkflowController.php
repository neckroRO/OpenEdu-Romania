<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ResourceVersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectResourceVersionRequest;
use App\Http\Resources\EditorialResourceVersionResource;
use App\Models\ResourceVersion;
use App\Services\ResourceEditorialWorkflow;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ResourceVersionWorkflowController extends Controller
{
    public function submit(
        ResourceVersion $resourceVersion,
        ResourceEditorialWorkflow $workflow
    ): JsonResponse {
        Gate::authorize(
            'submit',
            $resourceVersion
        );

        $version = $workflow->transition(
            $resourceVersion,
            ResourceVersionStatus::Submitted
        );

        return ApiResponse::success(
            (new EditorialResourceVersionResource(
                $version
            ))->resolve()
        );
    }

    public function approve(
        Request $request,
        ResourceVersion $resourceVersion,
        ResourceEditorialWorkflow $workflow
    ): JsonResponse {
        Gate::authorize(
            'approve',
            $resourceVersion
        );

        $version = $workflow->transition(
            $resourceVersion,
            ResourceVersionStatus::Approved,
            reviewedBy: $request->user()->id
        );

        return ApiResponse::success(
            (new EditorialResourceVersionResource(
                $version
            ))->resolve()
        );
    }

    public function reject(
        RejectResourceVersionRequest $request,
        ResourceVersion $resourceVersion,
        ResourceEditorialWorkflow $workflow
    ): JsonResponse {
        Gate::authorize(
            'reject',
            $resourceVersion
        );

        $validated = $request->validated();

        $version = $workflow->transition(
            $resourceVersion,
            ResourceVersionStatus::Rejected,
            reviewedBy: $request->user()->id,
            reviewNote: $validated['review_note']
        );

        return ApiResponse::success(
            (new EditorialResourceVersionResource(
                $version
            ))->resolve()
        );
    }

    public function publish(
        ResourceVersion $resourceVersion,
        ResourceEditorialWorkflow $workflow
    ): JsonResponse {
        Gate::authorize(
            'publish',
            $resourceVersion
        );

        $version = $workflow->transition(
            $resourceVersion,
            ResourceVersionStatus::Published
        );

        return ApiResponse::success(
            (new EditorialResourceVersionResource(
                $version
            ))->resolve()
        );
    }

    public function revise(
        ResourceVersion $resourceVersion,
        ResourceEditorialWorkflow $workflow
    ): JsonResponse {
        Gate::authorize(
            'revise',
            $resourceVersion
        );

        $version = $workflow->transition(
            $resourceVersion,
            ResourceVersionStatus::Draft
        );

        return ApiResponse::success(
            (new EditorialResourceVersionResource(
                $version
            ))->resolve()
        );
    }
}
