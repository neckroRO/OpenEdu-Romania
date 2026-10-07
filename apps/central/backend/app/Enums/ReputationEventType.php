<?php

namespace App\Enums;

enum ReputationEventType: string
{
    case LessonPublished = 'lesson_published';
    case ReviewConfirmed = 'review_confirmed';
    case ReviewContradicted = 'review_contradicted';
    case LessonWithdrawn = 'lesson_withdrawn';
    case ManualAdjustment = 'manual_adjustment';
}
