<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompetencyResource;
use App\Models\Competency;
use App\Models\CurriculumSubject;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CurriculumSubjectCompetencyController extends Controller
{
    public function index(
        CurriculumSubject $curriculumSubject
    ): JsonResponse {
        $competencies = Competency::query()
            ->where(
                'curriculum_subject_id',
                $curriculumSubject->id
            )
            ->where('status', 'active')
            ->with([
                'competencyConcepts' => function ($query) {
                    $query
                        ->whereHas('concept', function ($conceptQuery) {
                            $conceptQuery->where('status', 'active');
                        })
                        ->with('concept')
                        ->orderBy('display_order')
                        ->orderBy('id');
                },
            ])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $grouped = $competencies->groupBy(
            fn (Competency $competency): string =>
                $competency->parent_competency_id === null
                    ? 'root'
                    : (string) $competency->parent_competency_id
        );

        $buildTree = function (string $parentKey) use (
            &$buildTree,
            $grouped
        ) {
            return $grouped
                ->get($parentKey, collect())
                ->map(
                    function (Competency $competency) use (
                        &$buildTree
                    ) {
                        $competency->setRelation(
                            'children',
                            $buildTree((string) $competency->id)
                        );

                        return $competency;
                    }
                )
                ->values();
        };

        $tree = $buildTree('root');

        return ApiResponse::success(
            CompetencyResource::collection($tree)->resolve()
        );
    }
}
