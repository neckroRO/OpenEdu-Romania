<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ResourceVersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListResourcesRequest;
use App\Http\Resources\ResourceDetailResource;
use App\Models\Resource as EducationalResource;
use App\Models\ResourceVersion;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ResourceIndexController extends Controller
{
    public function __invoke(
        ListResourcesRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $publishedStatus =
            ResourceVersionStatus::Published->value;

        $versionFilters = function (
            $query
        ) use (
            $validated,
            $publishedStatus
        ): void {
            $query
                ->where('status', $publishedStatus)
                ->whereNotNull('published_at')
                ->where(
                    'version_number',
                    function ($subquery) use (
                        $publishedStatus
                    ): void {
                        $subquery
                            ->selectRaw(
                                'MAX(rv_latest.version_number)'
                            )
                            ->from(
                                'resource_versions as rv_latest'
                            )
                            ->whereColumn(
                                'rv_latest.resource_id',
                                'resource_versions.resource_id'
                            )
                            ->where(
                                'rv_latest.status',
                                $publishedStatus
                            )
                            ->whereNotNull(
                                'rv_latest.published_at'
                            );
                    }
                );

            if (! empty($validated['q'])) {
                $search = $validated['q'];

                $query->where(function (Builder $query) use (
                    $search
                ): void {
                    $query
                        ->where(
                            'title',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'summary',
                            'like',
                            '%' . $search . '%'
                        );
                });
            }

            if (isset($validated['language_code'])) {
                $query->where(
                    'language_code',
                    $validated['language_code']
                );
            }

            if (isset($validated['difficulty_level'])) {
                $query->where(
                    'difficulty_level',
                    $validated['difficulty_level']
                );
            }

            if (isset($validated['complexity_level'])) {
                $query->where(
                    'complexity_level',
                    $validated['complexity_level']
                );
            }
        };

        $query = EducationalResource::query()
            ->where('status', 'active')
            ->whereHas(
                'versions',
                $versionFilters
            )
            ->with([
                'versions' => function (
                    $query
                ) use ($versionFilters): void {
                    $versionFilters($query);

                    $query->orderByDesc(
                        'version_number'
                    );
                },
            ]);

        if (isset($validated['type'])) {
            $query->where(
                'type',
                $validated['type']
            );
        }

        if (isset($validated['concept_id'])) {
            $query->whereHas(
                'resourceConcepts',
                function (Builder $query) use (
                    $validated
                ): void {
                    $query->where(
                        'concept_id',
                        $validated['concept_id']
                    );
                }
            );
        }

        $sort = $validated['sort']
            ?? 'published_at';

        $direction = $validated['direction']
            ?? 'desc';

        $sortSubquery = ResourceVersion::query()
            ->select($sort)
            ->whereColumn(
                'resource_id',
                'resources.id'
            )
            ->where(
                'status',
                $publishedStatus
            )
            ->whereNotNull('published_at')
            ->orderByDesc('version_number')
            ->limit(1);

        $query
            ->orderBy(
                $sortSubquery,
                $direction
            )
            ->orderBy('resources.id');

        $perPage = $validated['per_page']
            ?? 20;

        $paginator = $query
            ->paginate($perPage)
            ->withQueryString();

        $data = ResourceDetailResource::collection(
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
