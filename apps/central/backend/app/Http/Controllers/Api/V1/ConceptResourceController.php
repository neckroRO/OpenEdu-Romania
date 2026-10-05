<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResourceConceptResource;
use App\Models\Concept;
use App\Models\ResourceConcept;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ConceptResourceController extends Controller
{
    public function index(Concept $concept): JsonResponse
    {
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
            ->whereHas('resource', function ($query) {
                $query
                    ->where('status', 'active')
                    ->whereHas('versions', function ($query) {
                        $query
                            ->where('status', 'published')
                            ->whereNotNull('published_at');
                    });
            })
            ->orderByDesc('is_primary')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            ResourceConceptResource::collection(
                $resourceConcepts
            )->resolve()
        );
    }
}
