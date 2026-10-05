<?php

namespace Tests\Unit;

use App\Enums\ResourceVersionStatus;
use PHPUnit\Framework\TestCase;

class ResourceVersionStatusTest extends TestCase
{
    public function test_allowed_editorial_transitions(): void
    {
        $this->assertTrue(
            ResourceVersionStatus::Draft
                ->canTransitionTo(ResourceVersionStatus::Submitted)
        );

        $this->assertTrue(
            ResourceVersionStatus::Submitted
                ->canTransitionTo(ResourceVersionStatus::Approved)
        );

        $this->assertTrue(
            ResourceVersionStatus::Submitted
                ->canTransitionTo(ResourceVersionStatus::Rejected)
        );

        $this->assertTrue(
            ResourceVersionStatus::Rejected
                ->canTransitionTo(ResourceVersionStatus::Draft)
        );

        $this->assertTrue(
            ResourceVersionStatus::Approved
                ->canTransitionTo(ResourceVersionStatus::Published)
        );
    }

    public function test_invalid_editorial_transitions_are_rejected(): void
    {
        $this->assertFalse(
            ResourceVersionStatus::Draft
                ->canTransitionTo(ResourceVersionStatus::Published)
        );

        $this->assertFalse(
            ResourceVersionStatus::Draft
                ->canTransitionTo(ResourceVersionStatus::Approved)
        );

        $this->assertFalse(
            ResourceVersionStatus::Rejected
                ->canTransitionTo(ResourceVersionStatus::Published)
        );

        $this->assertFalse(
            ResourceVersionStatus::Approved
                ->canTransitionTo(ResourceVersionStatus::Draft)
        );

        $this->assertFalse(
            ResourceVersionStatus::Published
                ->canTransitionTo(ResourceVersionStatus::Draft)
        );

        $this->assertFalse(
            ResourceVersionStatus::Published
                ->canTransitionTo(ResourceVersionStatus::Submitted)
        );
    }
}
