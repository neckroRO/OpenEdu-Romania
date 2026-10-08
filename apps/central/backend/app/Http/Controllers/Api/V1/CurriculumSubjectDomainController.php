<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DomainResource;
use App\Models\CurriculumSubject;
use App\Models\Domain;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CurriculumSubjectDomainController extends Controller
{
    public function index(
        CurriculumSubject $curriculumSubject
    ): JsonResponse {
        $domains = Domain::query()
            ->where(
                'curriculum_subject_id',
                $curriculumSubject->id
            )
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            DomainResource::collection(
                $domains
            )->resolve()
        );
    }
}
