<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ResourceVersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Http\Resources\ResourceDetailResource;
use App\Models\Resource as EducationalResource;
use App\Models\ResourceConcept;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ResourceController extends Controller
{
    public function store(
        StoreResourceRequest $request
    ): JsonResponse {
        Gate::authorize(
            'create',
            EducationalResource::class
        );

        $validated = $request->validated();
        $user = $request->user();

        $resource = DB::transaction(
            function () use ($validated, $user) {
                $resource = EducationalResource::create([
                    'code' => 'res-' . Str::uuid(),
                    'type' => $validated['type'],
                    'status' => 'active',
                ]);

                $resource->versions()->create([
                    'version_number' => 1,
                    'title' => $validated['title'],
                    'summary' => $validated['summary'] ?? null,
                    'content' => $validated['content'] ?? null,
                    'source_url' => $validated['source_url'] ?? null,
                    'language_code' =>
                        $validated['language_code'] ?? 'ro',
                    'difficulty_level' =>
                        $validated['difficulty_level'] ?? 1,
                    'complexity_level' =>
                        $validated['complexity_level'] ?? 1,
                    'status' => ResourceVersionStatus::Draft,
                    'created_by' => $user->id,
                ]);

                ResourceConcept::create([
                    'resource_id' => $resource->id,
                    'concept_id' => $validated['concept_id'],
                    'is_primary' => true,
                    'display_order' => 1,
                ]);

                return $resource;
            }
        );

        $resource->load([
            'versions',
            'resourceConcepts',
        ]);

        return ApiResponse::success(
            (new ResourceDetailResource($resource))->resolve(),
            201
        );
    }

    public function update(
        UpdateResourceRequest $request,
        EducationalResource $resource
    ): JsonResponse {
        $validated = $request->validated();

        $draft = $resource->versions()
            ->where(
                'status',
                ResourceVersionStatus::Draft->value
            )
            ->orderByDesc('version_number')
            ->first();

        if ($draft === null) {
            return ApiResponse::error(
                code: 'RESOURCE_NOT_EDITABLE',
                message: 'Doar o versiune draft poate fi editată.',
                status: 409,
            );
        }

        Gate::authorize('update', $draft);

        DB::transaction(function () use (
            $validated,
            $resource,
            $draft
        ) {
            if (array_key_exists('type', $validated)) {
                $resource->update([
                    'type' => $validated['type'],
                ]);
            }

            $versionFields = array_intersect_key(
                $validated,
                array_flip([
                    'title',
                    'summary',
                    'content',
                    'source_url',
                    'language_code',
                    'difficulty_level',
                    'complexity_level',
                ])
            );

            if ($versionFields !== []) {
                $draft->update($versionFields);
            }

            if (array_key_exists('concept_id', $validated)) {
                ResourceConcept::updateOrCreate(
                    [
                        'resource_id' => $resource->id,
                        'is_primary' => true,
                    ],
                    [
                        'concept_id' => $validated['concept_id'],
                        'display_order' => 1,
                    ]
                );
            }
        });

        $resource->refresh();
        $draft->refresh();

        $resource->setRelation(
            'versions',
            collect([$draft])
        );

        return ApiResponse::success(
            (new ResourceDetailResource($resource))->resolve()
        );
    }

    public function show(
        EducationalResource $resource
    ): JsonResponse {
        $resource = EducationalResource::query()
            ->with([
                'versions' => function ($query) {
                    $query
                        ->where('status', 'published')
                        ->whereNotNull('published_at')
                        ->orderByDesc('version_number');
                },
            ])
            ->whereKey($resource->id)
            ->where('status', 'active')
            ->whereHas('versions', function ($query) {
                $query
                    ->where('status', 'published')
                    ->whereNotNull('published_at');
            })
            ->first();

        if ($resource === null) {
            abort(404);
        }

        return ApiResponse::success(
            (new ResourceDetailResource($resource))->resolve()
        );
    }
}
