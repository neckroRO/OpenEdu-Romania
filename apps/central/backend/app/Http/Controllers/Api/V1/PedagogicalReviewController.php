<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LessonReviewVerdict;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePedagogicalReviewRequest;
use App\Models\LessonVersion;
use App\Services\PedagogicalReviewService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PedagogicalReviewController extends Controller
{
    public function index(
        LessonVersion $lessonVersion
    ): JsonResponse {
        Gate::authorize(
            'viewPedagogicalReviews',
            $lessonVersion
        );

        $reviews = $lessonVersion->reviews()
            ->where(
                'review_round',
                $lessonVersion->review_round
            )
            ->with('reviewer:id,name')
            ->orderBy('id')
            ->get()
            ->map(fn ($review) => [
                'id' => $review->id,
                'review_round' => $review->review_round,

                'reviewer' => $review->reviewer === null
                    ? null
                    : [
                        'id' => $review->reviewer->id,
                        'name' => $review->reviewer->name,
                    ],

                'verdict' => $review->verdict->value,

                'scores' => [
                    'correctness' =>
                        $review->correctness_score,

                    'curriculum_alignment' =>
                        $review->curriculum_alignment_score,

                    'clarity' =>
                        $review->clarity_score,

                    'pedagogical_value' =>
                        $review->pedagogical_value_score,

                    'difficulty_fit' =>
                        $review->difficulty_fit_score,
                ],

                'comment' => $review->comment,

                'weight_snapshot' =>
                    (float) $review->weight_snapshot,

                'created_at' =>
                    $review->created_at?->toISOString(),
            ])
            ->values()
            ->all();

        return ApiResponse::success([
            'lesson_version_id' => $lessonVersion->id,
            'review_round' => $lessonVersion->review_round,
            'reviews' => $reviews,
        ]);
    }

    public function store(
        StorePedagogicalReviewRequest $request,
        LessonVersion $lessonVersion,
        PedagogicalReviewService $service
    ): JsonResponse {
        Gate::authorize(
            'review',
            $lessonVersion
        );

        $validated = $request->validated();

        $review = $service->submitReview(
            $lessonVersion,
            $request->user(),
            LessonReviewVerdict::from(
                $validated['verdict']
            ),
            [
                'correctness_score' =>
                    $validated['correctness_score'],

                'curriculum_alignment_score' =>
                    $validated[
                        'curriculum_alignment_score'
                    ],

                'clarity_score' =>
                    $validated['clarity_score'],

                'pedagogical_value_score' =>
                    $validated[
                        'pedagogical_value_score'
                    ],

                'difficulty_fit_score' =>
                    $validated['difficulty_fit_score'],
            ],
            $validated['comment'] ?? null
        );

        return ApiResponse::created([
            'id' => $review->id,
            'lesson_version_id' =>
                $review->lesson_version_id,
            'review_round' => $review->review_round,
            'reviewer_id' => $review->reviewer_id,
            'verdict' => $review->verdict->value,

            'scores' => [
                'correctness' =>
                    $review->correctness_score,

                'curriculum_alignment' =>
                    $review->curriculum_alignment_score,

                'clarity' =>
                    $review->clarity_score,

                'pedagogical_value' =>
                    $review->pedagogical_value_score,

                'difficulty_fit' =>
                    $review->difficulty_fit_score,
            ],

            'comment' => $review->comment,

            'weight_snapshot' =>
                (float) $review->weight_snapshot,

            'created_at' =>
                $review->created_at?->toISOString(),
        ]);
    }

    public function consensus(
        Request $request,
        LessonVersion $lessonVersion,
        PedagogicalReviewService $service
    ): JsonResponse {
        Gate::authorize(
            'viewPedagogicalConsensus',
            $lessonVersion
        );

        $consensus = $service->consensus(
            $lessonVersion
        );

        return ApiResponse::success([
            'lesson_version_id' => $lessonVersion->id,
            'review_round' => $lessonVersion->review_round,

            'status' =>
                $consensus['status']->value,

            'reviewer_count' =>
                $consensus['reviewer_count'],

            'weights' => [
                'total' =>
                    $consensus['total_weight'],

                'approve' =>
                    $consensus['approve_weight'],

                'changes_requested' =>
                    $consensus[
                        'changes_requested_weight'
                    ],

                'reject' =>
                    $consensus['reject_weight'],
            ],
        ]);
    }
}
