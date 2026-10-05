<?php

namespace App\Services;

use App\Enums\ResourceVersionStatus;
use App\Exceptions\InvalidEditorialTransitionException;
use App\Models\ResourceVersion;

class ResourceEditorialWorkflow
{
    public function transition(
        ResourceVersion $version,
        ResourceVersionStatus $target,
        ?int $reviewedBy = null,
        ?string $reviewNote = null
    ): ResourceVersion {
        $current = $version->status;

        if (! $current->canTransitionTo($target)) {
            throw new InvalidEditorialTransitionException(
                $current,
                $target
            );
        }

        $version->status = $target;

        match ($target) {
            ResourceVersionStatus::Submitted => $this->markSubmitted(
                $version
            ),

            ResourceVersionStatus::Approved,
            ResourceVersionStatus::Rejected => $this->markReviewed(
                $version,
                $reviewedBy,
                $reviewNote
            ),

            ResourceVersionStatus::Draft => $this->returnToDraft(
                $version
            ),

            ResourceVersionStatus::Published => $this->markPublished(
                $version
            ),
        };

        $version->save();

        return $version->refresh();
    }

    private function markSubmitted(ResourceVersion $version): void
    {
        $version->submitted_at = now();
    }

    private function markReviewed(
        ResourceVersion $version,
        ?int $reviewedBy,
        ?string $reviewNote
    ): void {
        $version->reviewed_by = $reviewedBy;
        $version->reviewed_at = now();
        $version->review_note = $reviewNote;
    }

    private function returnToDraft(ResourceVersion $version): void
    {
        $version->submitted_at = null;
    }

    private function markPublished(ResourceVersion $version): void
    {
        $version->published_at = now();
    }
}
