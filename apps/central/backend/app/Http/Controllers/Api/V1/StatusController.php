<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'service' => 'openedu-central-api',
            'status' => 'ok',
            'api_version' => 'v1',
        ]);
    }
}
