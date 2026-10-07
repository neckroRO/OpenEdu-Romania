<?php

namespace App\Services;

use App\Enums\ReputationEventType;
use App\Models\LessonVersion;
use App\Models\LessonVersionReview;
use App\Models\ReputationEvent;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSubjectReputation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReputationService
{
    public function record(
        User $user,
        Subject $subject,
        ReputationEventType $type,
        ?string $eventKey = null,
        ?LessonVersion $lessonVersion = null,
        ?LessonVersionReview $review = null,
        array $metadata = [],
        ?int $manualPoints = null
    ): ReputationEvent {
        $points = $this->resolvePoints(
            $type,
            $manualPoints
        );

        return DB::transaction(function () use (
            $user,
            $subject,
            $type,
            $eventKey,
            $lessonVersion,
            $review,
            $metadata,
            $points
        ): ReputationEvent {
            if ($eventKey !== null) {
                $existing = ReputationEvent::query()
                    ->where('event_key', $eventKey)
                    ->first();

                if ($existing !== null) {
                    $this->assertMatchingEvent(
                        $existing,
                        $user,
                        $subject,
                        $type,
                        $lessonVersion,
                        $review,
                        $points
                    );

                    return $existing;
                }
            }

            $reputation = UserSubjectReputation::query()
                ->where('user_id', $user->id)
                ->where('subject_id', $subject->id)
                ->lockForUpdate()
                ->first();

            if ($reputation === null) {
                $reputation = UserSubjectReputation::create([
                    'user_id' => $user->id,
                    'subject_id' => $subject->id,
                    'score' => 0,
                    'contribution_count' => 0,
                    'review_count' => 0,
                ]);
            }

            $event = ReputationEvent::create([
                'user_id' => $user->id,
                'subject_id' => $subject->id,
                'event_type' => $type,
                'event_key' => $eventKey,
                'points' => $points,
                'lesson_version_id' => $lessonVersion?->id,
                'lesson_version_review_id' => $review?->id,
                'metadata' => $metadata ?: null,
            ]);

            $reputation->score += $points;

            if ($type === ReputationEventType::LessonPublished) {
                $reputation->contribution_count++;
            }

            if (in_array(
                $type,
                [
                    ReputationEventType::ReviewConfirmed,
                    ReputationEventType::ReviewContradicted,
                ],
                true
            )) {
                $reputation->review_count++;
            }

            $reputation->save();

            return $event->refresh();
        });
    }

    private function assertMatchingEvent(
        ReputationEvent $existing,
        User $user,
        Subject $subject,
        ReputationEventType $type,
        ?LessonVersion $lessonVersion,
        ?LessonVersionReview $review,
        int $points
    ): void {
        $matches = $existing->user_id === $user->id
            && $existing->subject_id === $subject->id
            && $existing->event_type === $type
            && $existing->points === $points
            && $existing->lesson_version_id === $lessonVersion?->id
            && $existing->lesson_version_review_id === $review?->id;

        if (! $matches) {
            throw new InvalidArgumentException(
                'The reputation event key is already used by another event.'
            );
        }
    }

    private function resolvePoints(
        ReputationEventType $type,
        ?int $manualPoints
    ): int {
        if (
            $type !== ReputationEventType::ManualAdjustment
            && $manualPoints !== null
        ) {
            throw new InvalidArgumentException(
                'Points can only be specified for manual adjustments.'
            );
        }

        return match ($type) {
            ReputationEventType::LessonPublished => 10,
            ReputationEventType::ReviewConfirmed => 3,
            ReputationEventType::ReviewContradicted => -3,
            ReputationEventType::LessonWithdrawn => -8,

            ReputationEventType::ManualAdjustment =>
                $manualPoints
                ?? throw new InvalidArgumentException(
                    'Manual adjustments require an explicit point value.'
                ),
        };
    }
}
