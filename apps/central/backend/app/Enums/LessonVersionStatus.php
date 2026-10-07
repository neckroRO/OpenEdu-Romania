<?php

namespace App\Enums;

enum LessonVersionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Rejected = 'rejected';
    case Approved = 'approved';
    case Published = 'published';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => $target === self::Submitted,

            self::Submitted => in_array(
                $target,
                [
                    self::Approved,
                    self::Rejected,
                ],
                true
            ),

            self::Rejected => $target === self::Draft,

            self::Approved => $target === self::Published,

            self::Published => false,
        };
    }
}
