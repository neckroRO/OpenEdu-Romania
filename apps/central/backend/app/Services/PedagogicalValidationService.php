<?php

namespace App\Services;

use App\Enums\LessonReviewVerdict;
use App\Enums\LessonVersionStatus;
use App\Enums\PedagogicalConsensusStatus;
use App\Enums\ReputationEventType;
use App\Exceptions\PedagogicalValidationException;
use App\Models\LessonVersion;
use App\Models\LessonVersionReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PedagogicalValidationService
{
    public function __construct(
        private readonly PedagogicalReviewService $reviews,
        private readonly LessonEditorialWorkflow $workflow,
        private readonly ReputationService $reputation
    ) {
    }

    public function moderate(
        LessonVersion $version,
        User $moderator,
        LessonVersionStatus $decision,
        ?string $note = null
    ): LessonVersion {
        if (! in_array(
            $decision,
            [
                LessonVersionStatus::Approved,
                LessonVersionStatus::Rejected,
            ],
            true
        )) {
            throw new PedagogicalValidationException(
                'Pedagogical moderation only supports approved or rejected decisions.'
            );
        }

        if (! $moderator->role->canModerate()) {
            throw new PedagogicalValidationException(
                'The user is not allowed to moderate lesson versions.'
            );
        }

        return DB::transaction(function () use (
            $version,
            $moderator,
            $decision,
            $note
        ): LessonVersion {
            $lockedVersion = LessonVersion::query()
                ->whereKey($version->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedVersion->status
                !== LessonVersionStatus::Submitted
            ) {
                throw new PedagogicalValidationException(
                    'Only submitted lesson versions can be moderated.'
                );
            }

            $consensus = $this->reviews->consensus(
                $lockedVersion
            );

            if (
                $consensus['status']
                === PedagogicalConsensusStatus::Pending
            ) {
                throw new PedagogicalValidationException(
                    'Pedagogical moderation requires at least three reviews.'
                );
            }

            $reviews = $lockedVersion->reviews()
                ->where(
                    'review_round',
                    $lockedVersion->review_round
                )
                ->with('reviewer')
                ->get();

            $result = $this->workflow->transition(
                $lockedVersion,
                $decision,
                $moderator->id,
                $note
            );

            $result->loadMissing(
                'lesson.curriculumSubject.subject'
            );

            $subject = $result
                ->lesson
                ->curriculumSubject
                ->subject;

            foreach ($reviews as $review) {
                if ($review->reviewer === null) {
                    continue;
                }

                $confirmed = $this->reviewAgreesWithDecision(
                    $review,
                    $decision
                );

                $type = $confirmed
                    ? ReputationEventType::ReviewConfirmed
                    : ReputationEventType::ReviewContradicted;

                $this->reputation->record(
                    $review->reviewer,
                    $subject,
                    $type,
                    'lesson-review:'.$review->id.':editorial-decision',
                    $result,
                    $review,
                    [
                        'review_round' => $review->review_round,
                        'review_verdict' => $review->verdict->value,
                        'editorial_decision' => $decision->value,
                        'consensus_status' =>
                            $consensus['status']->value,
                    ]
                );
            }

            return $result->refresh();
        });
    }

    public function publish(
        LessonVersion $version,
        User $moderator
    ): LessonVersion {
        if (! $moderator->role->canModerate()) {
            throw new PedagogicalValidationException(
                'The user is not allowed to publish lesson versions.'
            );
        }

        return DB::transaction(function () use (
            $version
        ): LessonVersion {
            $lockedVersion = LessonVersion::query()
                ->whereKey($version->id)
                ->lockForUpdate()
                ->firstOrFail();

            $result = $this->workflow->transition(
                $lockedVersion,
                LessonVersionStatus::Published
            );

            $result->loadMissing([
                'creator',
                'lesson.curriculumSubject.subject',
            ]);

            if ($result->creator !== null) {
                $subject = $result
                    ->lesson
                    ->curriculumSubject
                    ->subject;

                $this->reputation->record(
                    $result->creator,
                    $subject,
                    ReputationEventType::LessonPublished,
                    'lesson-version:'.$result->id.':published',
                    $result,
                    metadata: [
                        'version_number' =>
                            $result->version_number,
                        'review_round' =>
                            $result->review_round,
                    ]
                );
            }

            return $result->refresh();
        });
    }

    private function reviewAgreesWithDecision(
        LessonVersionReview $review,
        LessonVersionStatus $decision
    ): bool {
        if ($decision === LessonVersionStatus::Approved) {
            return $review->verdict
                === LessonReviewVerdict::Approve;
        }

        return in_array(
            $review->verdict,
            [
                LessonReviewVerdict::ChangesRequested,
                LessonReviewVerdict::Reject,
            ],
            true
        );
    }
}
