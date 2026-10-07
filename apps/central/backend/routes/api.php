<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConceptResourceController;
use App\Http\Controllers\Api\V1\CurriculumImportController;
use App\Http\Controllers\Api\V1\CurriculumSubjectCompetencyController;
use App\Http\Controllers\Api\V1\CurriculumSubjectConceptController;
use App\Http\Controllers\Api\V1\CurriculumSubjectLessonController;
use App\Http\Controllers\Api\V1\EducationLevelController;
use App\Http\Controllers\Api\V1\EducationLevelSubjectController;
use App\Http\Controllers\Api\V1\EditorialLessonIndexController;
use App\Http\Controllers\Api\V1\EditorialResourceIndexController;
use App\Http\Controllers\Api\V1\LessonConceptController;
use App\Http\Controllers\Api\V1\LessonController;
use App\Http\Controllers\Api\V1\LessonModerationQueueController;
use App\Http\Controllers\Api\V1\LessonVersionController;
use App\Http\Controllers\Api\V1\LessonVersionResourceController;
use App\Http\Controllers\Api\V1\LessonVersionWorkflowController;
use App\Http\Controllers\Api\V1\ModerationQueueController;
use App\Http\Controllers\Api\V1\PedagogicalReviewController;
use App\Http\Controllers\Api\V1\ResourceController;
use App\Http\Controllers\Api\V1\ResourceIndexController;
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
            '/admin/curriculum/import',
            CurriculumImportController::class
        );

        Route::get(
            '/editor/resources',
            EditorialResourceIndexController::class
        );

        Route::get(
            '/editor/lessons',
            EditorialLessonIndexController::class
        );

        Route::get(
            '/editor/lesson-moderation',
            LessonModerationQueueController::class
        );

        Route::get(
            '/editor/moderation',
            ModerationQueueController::class
        );

        Route::post(
            '/resources',
            [ResourceController::class, 'store']
        );

        Route::post(
            '/lessons/{lesson}/versions',
            [LessonVersionController::class, 'store']
        );

        Route::put(
            '/lessons/{lesson}/concepts',
            [LessonConceptController::class, 'update']
        );

        Route::put(
            '/lesson-versions/{lessonVersion}/resources',
            [LessonVersionResourceController::class, 'update']
        );

        Route::patch(
            '/lesson-versions/{lessonVersion}',
            [LessonVersionController::class, 'update']
        );

        Route::post(
            '/lesson-versions/{lessonVersion}/submit',
            [LessonVersionWorkflowController::class, 'submit']
        );

        Route::post(
            '/lesson-versions/{lessonVersion}/reviews',
            [PedagogicalReviewController::class, 'store']
        );

        Route::get(
            '/lesson-versions/{lessonVersion}/reviews',
            [PedagogicalReviewController::class, 'index']
        );

        Route::get(
            '/lesson-versions/{lessonVersion}/consensus',
            [PedagogicalReviewController::class, 'consensus']
        );

        Route::post(
            '/lesson-versions/{lessonVersion}/approve',
            [LessonVersionWorkflowController::class, 'approve']
        );

        Route::post(
            '/lesson-versions/{lessonVersion}/reject',
            [LessonVersionWorkflowController::class, 'reject']
        );

        Route::post(
            '/lesson-versions/{lessonVersion}/publish',
            [LessonVersionWorkflowController::class, 'publish']
        );

        Route::post(
            '/lesson-versions/{lessonVersion}/revise',
            [LessonVersionWorkflowController::class, 'revise']
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
    '/resources',
    ResourceIndexController::class
);

Route::get(
    '/resources/{resource}',
    [ResourceController::class, 'show']
);

Route::get(
    '/lessons/{lesson}',
    [LessonController::class, 'show']
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
    '/curriculum-subjects/{curriculumSubject}/competencies',
    [CurriculumSubjectCompetencyController::class, 'index']
);

Route::get(
    '/curriculum-subjects/{curriculumSubject}/lessons',
    [CurriculumSubjectLessonController::class, 'index']
);

Route::get(
    '/education-levels',
    [EducationLevelController::class, 'index']
);

Route::get(
    '/education-levels/{educationLevel}/subjects',
    [EducationLevelSubjectController::class, 'index']
);
