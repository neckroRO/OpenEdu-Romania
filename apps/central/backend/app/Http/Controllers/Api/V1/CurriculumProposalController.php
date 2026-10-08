<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CurriculumProposalType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCurriculumProposalRequest;
use App\Http\Resources\CurriculumProposalResource;
use App\Models\CurriculumProposal;
use App\Services\Curriculum\CurriculumProposalService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CurriculumProposalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user?->role->canContribute() !== true) {
            throw new AccessDeniedHttpException();
        }

        $query = CurriculumProposal::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! $user->role->canModerate()) {
            $query->where('proposed_by', $user->id);
        }

        $proposals = $query
            ->get()
            ->map(
                fn (CurriculumProposal $proposal) =>
                    (new CurriculumProposalResource($proposal))
                        ->resolve()
            )
            ->values()
            ->all();

        return ApiResponse::success($proposals);
    }

    public function store(
        StoreCurriculumProposalRequest $request,
        CurriculumProposalService $service
    ): JsonResponse {
        $validated = $request->validated();

        $proposal = $service->create(
            proposer: $request->user(),
            entityType: $validated['entity_type'],
            type: CurriculumProposalType::from(
                $validated['proposal_type']
            ),
            payload: $request->input('payload'),
            entityId: $validated['entity_id'] ?? null,
            reason: $validated['reason'] ?? null,
        );

        return ApiResponse::created(
            (new CurriculumProposalResource($proposal))
                ->resolve()
        );
    }
}
