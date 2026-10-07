<?php

namespace App\Enums;

enum LessonReviewVerdict: string
{
    case Approve = 'approve';
    case ChangesRequested = 'changes_requested';
    case Reject = 'reject';
}
