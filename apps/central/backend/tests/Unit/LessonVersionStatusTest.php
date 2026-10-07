<?php

namespace Tests\Unit;

use App\Enums\LessonVersionStatus;
use PHPUnit\Framework\TestCase;

class LessonVersionStatusTest extends TestCase
{
    public function test_allowed_editorial_transitions(): void
    {
        $this->assertTrue(
            LessonVersionStatus::Draft
                ->canTransitionTo(LessonVersionStatus::Submitted)
        );

        $this->assertTrue(
            LessonVersionStatus::Submitted
                ->canTransitionTo(LessonVersionStatus::Approved)
        );

        $this->assertTrue(
            LessonVersionStatus::Submitted
                ->canTransitionTo(LessonVersionStatus::Rejected)
        );

        $this->assertTrue(
            LessonVersionStatus::Rejected
                ->canTransitionTo(LessonVersionStatus::Draft)
        );

        $this->assertTrue(
            LessonVersionStatus::Approved
                ->canTransitionTo(LessonVersionStatus::Published)
        );
    }

    public function test_invalid_editorial_transitions_are_rejected(): void
    {
        $this->assertFalse(
            LessonVersionStatus::Draft
                ->canTransitionTo(LessonVersionStatus::Published)
        );

        $this->assertFalse(
            LessonVersionStatus::Submitted
                ->canTransitionTo(LessonVersionStatus::Draft)
        );

        $this->assertFalse(
            LessonVersionStatus::Rejected
                ->canTransitionTo(LessonVersionStatus::Published)
        );

        $this->assertFalse(
            LessonVersionStatus::Published
                ->canTransitionTo(LessonVersionStatus::Draft)
        );
    }
}
