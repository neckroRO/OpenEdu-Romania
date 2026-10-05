<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResourceDetailResource;
use App\Models\Resource as EducationalResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ResourceController extends Controller
{
    public function show(EducationalResource $resource): JsonResponse
    {
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
