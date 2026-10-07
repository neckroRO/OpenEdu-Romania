<?php

namespace App\Services;

use App\Enums\LessonVersionStatus;
use App\Exceptions\InvalidEditorialTransitionException;
use App\Models\LessonVersion;

class LessonEditorialWorkflow
{
    public function transition(
        LessonVersion $version,
        LessonVersionStatus $target,
        ?int $reviewedBy = null,
        ?string $reviewNote = null
    ): LessonVersion {
        $current = $version->status;

        if (! $current->canTransitionTo($target)) {
            throw new InvalidEditorialTransitionException(
                $current,
                $target
            );
        }

        $version->status = $target;

        match ($target) {
            LessonVersionStatus::Submitted => $this->markSubmitted(
                $version
            ),

            LessonVersionStatus::Approved,
            LessonVersionStatus::Rejected => $this->markReviewed(
                $version,
                $reviewedBy,
                $reviewNote
            ),

            LessonVersionStatus::Draft => $this->returnToDraft(
                $version
            ),

            LessonVersionStatus::Published => $this->markPublished(
                $version
            ),
        };

        $version->save();

        return $version->refresh();
    }

    private function markSubmitted(LessonVersion $version): void
    {
        $version->submitted_at = now();
    }

    private function markReviewed(
        LessonVersion $version,
        ?int $reviewedBy,
        ?string $reviewNote
    ): void {
        $version->reviewed_by = $reviewedBy;
        $version->reviewed_at = now();
        $version->review_note = $reviewNote;
    }

    private function returnToDraft(LessonVersion $version): void
    {
        $version->submitted_at = null;
    }

    private function markPublished(LessonVersion $version): void
    {
        $version->published_at = now();
    }
}
