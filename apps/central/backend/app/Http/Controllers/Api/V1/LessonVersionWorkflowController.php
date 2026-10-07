<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LessonVersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectLessonVersionRequest;
use App\Http\Resources\EditorialLessonVersionResource;
use App\Models\LessonVersion;
use App\Services\LessonEditorialWorkflow;
use App\Services\PedagogicalValidationService;
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
        PedagogicalValidationService $validation
    ): JsonResponse {
        Gate::authorize('approve', $lessonVersion);

        $version = $validation->moderate(
            $lessonVersion,
            $request->user(),
            LessonVersionStatus::Approved
        );

        return $this->response($version);
    }

    public function reject(
        RejectLessonVersionRequest $request,
        LessonVersion $lessonVersion,
        PedagogicalValidationService $validation
    ): JsonResponse {
        Gate::authorize('reject', $lessonVersion);

        $validated = $request->validated();

        $version = $validation->moderate(
            $lessonVersion,
            $request->user(),
            LessonVersionStatus::Rejected,
            $validated['review_note']
        );

        return $this->response($version);
    }

    public function publish(
        Request $request,
        LessonVersion $lessonVersion,
        PedagogicalValidationService $validation
    ): JsonResponse {
        Gate::authorize('publish', $lessonVersion);

        $version = $validation->publish(
            $lessonVersion,
            $request->user()
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
