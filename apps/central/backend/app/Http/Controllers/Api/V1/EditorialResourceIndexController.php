<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EditorialResourceResource;
use App\Models\Resource as EducationalResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EditorialResourceIndexController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize(
            'create',
            EducationalResource::class
        );

        $userId = $request->user()->id;

        $resources = EducationalResource::query()
            ->whereHas(
                'versions',
                function ($query) use ($userId): void {
                    $query->where(
                        'created_by',
                        $userId
                    );
                }
            )
            ->with([
                'versions' => function (
                    $query
                ) use ($userId): void {
                    $query
                        ->where(
                            'created_by',
                            $userId
                        )
                        ->orderByDesc(
                            'version_number'
                        );
                },
                'resourceConcepts' => function ($query): void {
                    $query
                        ->orderByDesc('is_primary')
                        ->orderBy('display_order')
                        ->with('concept');
                },
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $data = EditorialResourceResource::collection(
            $resources
        )->resolve();

        return ApiResponse::success($data);
    }
}
