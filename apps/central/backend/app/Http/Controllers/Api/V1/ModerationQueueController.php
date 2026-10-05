<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ResourceVersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ModerationQueueResource;
use App\Models\ResourceVersion;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ModerationQueueController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize(
            'viewAny',
            ResourceVersion::class
        );

        $versions = ResourceVersion::query()
            ->whereIn('status', [
                ResourceVersionStatus::Submitted->value,
                ResourceVersionStatus::Approved->value,
            ])
            ->with([
                'creator',
                'resource.resourceConcepts' =>
                    function ($query): void {
                        $query
                            ->orderByDesc('is_primary')
                            ->orderBy('display_order')
                            ->with('concept');
                    },
            ])
            ->orderByRaw(
                "CASE WHEN status = 'submitted' THEN 0 ELSE 1 END"
            )
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get();

        $data = ModerationQueueResource::collection(
            $versions
        )->resolve();

        return ApiResponse::success($data);
    }
}
