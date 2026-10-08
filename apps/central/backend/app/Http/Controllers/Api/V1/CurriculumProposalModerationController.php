<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewCurriculumProposalRequest;
use App\Http\Resources\CurriculumProposalResource;
use App\Models\CurriculumProposal;
use App\Services\Curriculum\CurriculumMergeModerationService;
use App\Services\Curriculum\CurriculumProposalWorkflow;
use App\Support\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CurriculumProposalModerationController extends Controller
{
    public function preview(
        Request $request,
        CurriculumProposal $proposal,
        CurriculumMergeModerationService $service
    ): JsonResponse {
        $this->authorizeModerator($request);

        try {
            $preview = $service->preview($proposal);
        } catch (DomainException $exception) {
            return ApiResponse::error(
                code: 'CURRICULUM_MERGE_PREVIEW_INVALID',
                message: $exception->getMessage(),
                status: 409,
            );
        }

        return ApiResponse::success(
            $preview->toArray()
        );
    }

    public function reject(
        ReviewCurriculumProposalRequest $request,
        CurriculumProposal $proposal,
        CurriculumProposalWorkflow $workflow
    ): JsonResponse {
        try {
            $proposal = $workflow->reject(
                $proposal,
                $request->user(),
                $request->validated()['note'] ?? null
            );
        } catch (DomainException $exception) {
            return ApiResponse::error(
                code: 'CURRICULUM_PROPOSAL_REVIEW_INVALID',
                message: $exception->getMessage(),
                status: 409,
            );
        }

        return ApiResponse::success(
            (new CurriculumProposalResource($proposal))
                ->resolve()
        );
    }

    public function confirmMerge(
        ReviewCurriculumProposalRequest $request,
        CurriculumProposal $proposal,
        CurriculumMergeModerationService $service
    ): JsonResponse {
        try {
            $merge = $service->confirm(
                $proposal,
                $request->user(),
                $request->validated()['note'] ?? null
            );
        } catch (DomainException $exception) {
            return ApiResponse::error(
                code: 'CURRICULUM_MERGE_CONFIRMATION_INVALID',
                message: $exception->getMessage(),
                status: 409,
            );
        }

        return ApiResponse::success([
            'proposal' => (
                new CurriculumProposalResource(
                    $proposal->refresh()
                )
            )->resolve(),

            'merge' => [
                'id' => (int) $merge->id,
                'entity_type' => $merge->entity_type,
                'source_entity_id' =>
                    (int) $merge->source_entity_id,
                'target_entity_id' =>
                    (int) $merge->target_entity_id,
                'merged_by' => $merge->merged_by === null
                    ? null
                    : (int) $merge->merged_by,
                'merged_at' =>
                    $merge->merged_at?->toISOString(),
                'reason' => $merge->reason,
                'metadata' => $merge->metadata,
            ],
        ]);
    }

    private function authorizeModerator(Request $request): void
    {
        if (
            $request->user()?->role->canModerate()
            !== true
        ) {
            throw new AccessDeniedHttpException();
        }
    }
}
