<?php

namespace App\Services;

use App\Enums\LessonReviewVerdict;
use App\Enums\LessonVersionStatus;
use App\Enums\PedagogicalConsensusStatus;
use App\Exceptions\PedagogicalReviewException;
use App\Models\LessonVersion;
use App\Models\LessonVersionReview;
use App\Models\User;
use App\Models\UserSubjectReputation;

class PedagogicalReviewService
{
    public const MIN_REVIEWERS = 3;

    /**
     * @param  array{
     *     correctness_score: int,
     *     curriculum_alignment_score: int,
     *     clarity_score: int,
     *     pedagogical_value_score: int,
     *     difficulty_fit_score: int
     * }  $scores
     */
    public function submitReview(
        LessonVersion $version,
        User $reviewer,
        LessonReviewVerdict $verdict,
        array $scores,
        ?string $comment = null
    ): LessonVersionReview {
        $this->assertReviewAllowed(
            $version,
            $reviewer,
            $scores
        );

        $weight = $this->reviewerWeight(
            $version,
            $reviewer
        );

        return LessonVersionReview::create([
            'lesson_version_id' => $version->id,
            'reviewer_id' => $reviewer->id,
            'review_round' => $version->review_round,
            'verdict' => $verdict,
            'correctness_score' => $scores['correctness_score'],
            'curriculum_alignment_score' =>
                $scores['curriculum_alignment_score'],
            'clarity_score' => $scores['clarity_score'],
            'pedagogical_value_score' =>
                $scores['pedagogical_value_score'],
            'difficulty_fit_score' =>
                $scores['difficulty_fit_score'],
            'comment' => $comment,
            'weight_snapshot' => $weight,
        ]);
    }

    /**
     * @return array{
     *     status: PedagogicalConsensusStatus,
     *     reviewer_count: int,
     *     total_weight: float,
     *     approve_weight: float,
     *     changes_requested_weight: float,
     *     reject_weight: float
     * }
     */
    public function consensus(
        LessonVersion $version
    ): array {
        $reviews = $version->reviews()
            ->where(
                'review_round',
                $version->review_round
            )
            ->get();

        /*
         * Review-urile sunt istorice.
         *
         * reviewer_id poate deveni NULL dacă utilizatorul este șters,
         * dar review-ul trebuie să continue să conteze în consens.
         *
         * Unicitatea reviewerului pe rundă este deja garantată în DB.
         */
        $reviewerCount = $reviews->count();

        $approveWeight = 0.0;
        $changesWeight = 0.0;
        $rejectWeight = 0.0;

        foreach ($reviews as $review) {
            $weight = (float) $review->weight_snapshot;

            match ($review->verdict) {
                LessonReviewVerdict::Approve =>
                    $approveWeight += $weight,

                LessonReviewVerdict::ChangesRequested =>
                    $changesWeight += $weight,

                LessonReviewVerdict::Reject =>
                    $rejectWeight += $weight,
            };
        }

        $totalWeight = $approveWeight
            + $changesWeight
            + $rejectWeight;

        $status = PedagogicalConsensusStatus::Pending;

        if (
            $reviewerCount >= self::MIN_REVIEWERS
            && $totalWeight > 0
        ) {
            $approveRatio = $approveWeight / $totalWeight;
            $rejectRatio = $rejectWeight / $totalWeight;

            if ($rejectRatio >= 0.50) {
                $status = PedagogicalConsensusStatus::Rejected;
            } elseif (
                $approveRatio >= (2 / 3)
                && $rejectWeight === 0.0
            ) {
                $status = PedagogicalConsensusStatus::Approved;
            } else {
                $status = PedagogicalConsensusStatus::ChangesRequested;
            }
        }

        return [
            'status' => $status,
            'reviewer_count' => $reviewerCount,
            'total_weight' => round($totalWeight, 2),
            'approve_weight' => round($approveWeight, 2),
            'changes_requested_weight' => round(
                $changesWeight,
                2
            ),
            'reject_weight' => round($rejectWeight, 2),
        ];
    }

    /**
     * @param  array<string, int>  $scores
     */
    private function assertReviewAllowed(
        LessonVersion $version,
        User $reviewer,
        array $scores
    ): void {
        if ($version->status !== LessonVersionStatus::Submitted) {
            throw new PedagogicalReviewException(
                'Only submitted lesson versions can be reviewed.'
            );
        }

        if (! $reviewer->role->canContribute()) {
            throw new PedagogicalReviewException(
                'The user is not allowed to perform pedagogical reviews.'
            );
        }

        if (
            $version->created_by !== null
            && $version->created_by === $reviewer->id
        ) {
            throw new PedagogicalReviewException(
                'Authors cannot review their own lesson versions.'
            );
        }

        $alreadyReviewed = LessonVersionReview::query()
            ->where('lesson_version_id', $version->id)
            ->where('review_round', $version->review_round)
            ->where('reviewer_id', $reviewer->id)
            ->exists();

        if ($alreadyReviewed) {
            throw new PedagogicalReviewException(
                'The reviewer has already reviewed this round.'
            );
        }

        $requiredScores = [
            'correctness_score',
            'curriculum_alignment_score',
            'clarity_score',
            'pedagogical_value_score',
            'difficulty_fit_score',
        ];

        foreach ($requiredScores as $scoreName) {
            if (! array_key_exists($scoreName, $scores)) {
                throw new PedagogicalReviewException(
                    "Missing review score: {$scoreName}."
                );
            }

            $score = $scores[$scoreName];

            if (
                ! is_int($score)
                || $score < 1
                || $score > 5
            ) {
                throw new PedagogicalReviewException(
                    "Review score {$scoreName} must be between 1 and 5."
                );
            }
        }
    }

    private function reviewerWeight(
        LessonVersion $version,
        User $reviewer
    ): float {
        $version->loadMissing(
            'lesson.curriculumSubject'
        );

        $subjectId = $version
            ->lesson
            ->curriculumSubject
            ->subject_id;

        $score = UserSubjectReputation::query()
            ->where('user_id', $reviewer->id)
            ->where('subject_id', $subjectId)
            ->value('score') ?? 0;

        return match (true) {
            $score >= 300 => 1.50,
            $score >= 150 => 1.25,
            $score >= 50 => 1.10,
            default => 1.00,
        };
    }
}
