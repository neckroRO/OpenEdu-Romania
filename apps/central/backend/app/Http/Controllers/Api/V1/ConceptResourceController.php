<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListConceptResourcesRequest;
use App\Http\Resources\ResourceConceptResource;
use App\Models\Concept;
use App\Models\ResourceConcept;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ConceptResourceController extends Controller
{
    public function index(
        ListConceptResourcesRequest $request,
        Concept $concept
    ): JsonResponse {
        $validated = $request->validated();

        $resourceConcepts = ResourceConcept::query()
            ->with([
                'resource.versions' => function ($query) {
                    $query
                        ->where('status', 'published')
                        ->whereNotNull('published_at')
                        ->orderByDesc('version_number');
                },
            ])
            ->where('concept_id', $concept->id)
            ->whereHas(
                'resource',
                function (Builder $query): void {
                    $query
                        ->where('status', 'active')
                        ->whereHas(
                            'versions',
                            function (Builder $query): void {
                                $query
                                    ->where(
                                        'status',
                                        'published'
                                    )
                                    ->whereNotNull(
                                        'published_at'
                                    );
                            }
                        );
                }
            )
            ->orderByDesc('is_primary')
            ->orderBy('display_order')
            ->orderBy('id');

        $perPage = $validated['per_page']
            ?? 20;

        $paginator = $resourceConcepts
            ->paginate($perPage)
            ->withQueryString();

        $data = ResourceConceptResource::collection(
            $paginator->getCollection()
        )->resolve();

        return ApiResponse::success(
            $data,
            meta: [
                'pagination' => [
                    'current_page' =>
                        $paginator->currentPage(),
                    'per_page' =>
                        $paginator->perPage(),
                    'total' =>
                        $paginator->total(),
                    'last_page' =>
                        $paginator->lastPage(),
                    'from' =>
                        $paginator->firstItem(),
                    'to' =>
                        $paginator->lastItem(),
                ],
            ]
        );
    }
}
