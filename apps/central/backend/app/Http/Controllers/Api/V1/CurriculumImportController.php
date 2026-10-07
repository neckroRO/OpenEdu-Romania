<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Curriculum\CurriculumImportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CurriculumImportController extends Controller
{
    public function __invoke(
        Request $request,
        CurriculumImportService $importService
    ): JsonResponse {
        $user = $request->user();

        if ($user === null || !$user->role->isAdmin()) {
            throw new AccessDeniedHttpException();
        }

        $dryRun = $request->boolean('dry_run');

        $payload = $request->json()->all();

        $result = $dryRun
            ? $importService->dryRun($payload)
            : $importService->import($payload);

        return ApiResponse::success([
            'mode' => $dryRun
                ? 'dry_run'
                : 'import',
            'persisted' => !$dryRun,
            'summary' => $result,
        ]);
    }
}
