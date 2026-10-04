<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'service' => 'openedu-central-api',
                'status' => 'ok',
                'api_version' => 'v1',
            ],
        ]);
    }
}