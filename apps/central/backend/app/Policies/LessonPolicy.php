<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role->isAdmin()) {
            return true;
        }

        return null;
    }

    public function updateConcepts(
        User $user,
        Lesson $lesson
    ): bool {
        return $user->role->canModerate();
    }
}
