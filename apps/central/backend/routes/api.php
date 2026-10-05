<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConceptResourceController;
use App\Http\Controllers\Api\V1\CurriculumSubjectConceptController;
use App\Http\Controllers\Api\V1\EducationLevelController;
use App\Http\Controllers\Api\V1\EducationLevelSubjectController;
use App\Http\Controllers\Api\V1\ResourceController;
use App\Http\Controllers\Api\V1\ResourceVersionWorkflowController;
use App\Http\Controllers\Api\V1\StatusController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/auth/login',
    [AuthController::class, 'login']
)->middleware('throttle:5,1');

Route::middleware('auth:sanctum')
    ->prefix('auth')
    ->group(function () {
        Route::get(
            '/me',
            [AuthController::class, 'me']
        );

        Route::post(
            '/logout',
            [AuthController::class, 'logout']
        );
    });

Route::middleware('auth:sanctum')
    ->group(function () {
        Route::post(
            '/resources',
            [ResourceController::class, 'store']
        );

        Route::patch(
            '/resources/{resource}',
            [ResourceController::class, 'update']
        );

        Route::post(
            '/resource-versions/{resourceVersion}/submit',
            [ResourceVersionWorkflowController::class, 'submit']
        );

        Route::post(
            '/resource-versions/{resourceVersion}/approve',
            [ResourceVersionWorkflowController::class, 'approve']
        );

        Route::post(
            '/resource-versions/{resourceVersion}/reject',
            [ResourceVersionWorkflowController::class, 'reject']
        );

        Route::post(
            '/resource-versions/{resourceVersion}/publish',
            [ResourceVersionWorkflowController::class, 'publish']
        );

        Route::post(
            '/resource-versions/{resourceVersion}/revise',
            [ResourceVersionWorkflowController::class, 'revise']
        );
    });

Route::get('/status', StatusController::class);

Route::get(
    '/resources/{resource}',
    [ResourceController::class, 'show']
);

Route::get(
    '/concepts/{concept}/resources',
    [ConceptResourceController::class, 'index']
);

Route::get(
    '/curriculum-subjects/{curriculumSubject}/concepts',
    [CurriculumSubjectConceptController::class, 'index']
);

Route::get(
    '/education-levels',
    [EducationLevelController::class, 'index']
);

Route::get(
    '/education-levels/{educationLevel}/subjects',
    [EducationLevelSubjectController::class, 'index']
);
