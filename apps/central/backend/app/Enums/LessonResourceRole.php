<?php

namespace App\Enums;

enum LessonResourceRole: string
{
    case Explanation = 'explanation';
    case Example = 'example';
    case Exercise = 'exercise';
    case Assessment = 'assessment';
    case Supplementary = 'supplementary';
}
