<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConceptPlacementResource;
use App\Models\ConceptPlacement;
use App\Models\CurriculumSubject;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CurriculumSubjectConceptController extends Controller
{
    public function index(
        CurriculumSubject $curriculumSubject
    ): JsonResponse {
        $placements = ConceptPlacement::query()
            ->with([
                'domain',
                'concept',
            ])
            ->whereHas('domain', function ($query) use ($curriculumSubject) {
                $query->where(
                    'curriculum_subject_id',
                    $curriculumSubject->id
                );
            })
            ->whereHas('concept', function ($query) {
                $query->where('status', 'active');
            })
            ->join(
                'domains',
                'concept_placements.domain_id',
                '=',
                'domains.id'
            )
            ->orderBy('domains.display_order')
            ->orderBy('concept_placements.display_order')
            ->orderBy('concept_placements.id')
            ->select('concept_placements.*')
            ->get();

        return ApiResponse::success(
            ConceptPlacementResource::collection($placements)->resolve()
        );
    }
}
