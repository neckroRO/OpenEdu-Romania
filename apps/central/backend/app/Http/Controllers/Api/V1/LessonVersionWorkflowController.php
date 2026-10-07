<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LessonVersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectLessonVersionRequest;
use App\Http\Resources\EditorialLessonVersionResource;
use App\Models\LessonVersion;
use App\Services\LessonEditorialWorkflow;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LessonVersionWorkflowController extends Controller
{
    public function submit(
        LessonVersion $lessonVersion,
        LessonEditorialWorkflow $workflow
    ): JsonResponse {
        Gate::authorize('submit', $lessonVersion);

        $version = $workflow->transition(
            $lessonVersion,
            LessonVersionStatus::Submitted
        );

        return $this->response($version);
    }

    public function approve(
        Request $request,
        LessonVersion $lessonVersion,
        LessonEditorialWorkflow $workflow
    ): JsonResponse {
        Gate::authorize('approve', $lessonVersion);

        $version = $workflow->transition(
            $lessonVersion,
            LessonVersionStatus::Approved,
            reviewedBy: $request->user()->id
        );

        return $this->response($version);
    }

    public function reject(
        RejectLessonVersionRequest $request,
        LessonVersion $lessonVersion,
        LessonEditorialWorkflow $workflow
    ): JsonResponse {
        Gate::authorize('reject', $lessonVersion);

        $validated = $request->validated();

        $version = $workflow->transition(
            $lessonVersion,
            LessonVersionStatus::Rejected,
            reviewedBy: $request->user()->id,
            reviewNote: $validated['review_note']
        );

        return $this->response($version);
    }

    public function publish(
        LessonVersion $lessonVersion,
        LessonEditorialWorkflow $workflow
    ): JsonResponse {
        Gate::authorize('publish', $lessonVersion);

        $version = $workflow->transition(
            $lessonVersion,
            LessonVersionStatus::Published
        );

        return $this->response($version);
    }

    public function revise(
        LessonVersion $lessonVersion,
        LessonEditorialWorkflow $workflow
    ): JsonResponse {
        Gate::authorize('revise', $lessonVersion);

        $version = $workflow->transition(
            $lessonVersion,
            LessonVersionStatus::Draft
        );

        return $this->response($version);
    }

    private function response(
        LessonVersion $version
    ): JsonResponse {
        return ApiResponse::success(
            (new EditorialLessonVersionResource(
                $version
            ))->resolve()
        );
    }
}
