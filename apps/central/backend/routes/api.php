<?php

use App\Http\Controllers\Api\V1\ResourceController;
use App\Http\Controllers\Api\V1\ConceptResourceController;
use App\Http\Controllers\Api\V1\CurriculumSubjectConceptController;
use App\Http\Controllers\Api\V1\EducationLevelController;
use App\Http\Controllers\Api\V1\EducationLevelSubjectController;
use App\Http\Controllers\Api\V1\StatusController;
use Illuminate\Support\Facades\Route;

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
