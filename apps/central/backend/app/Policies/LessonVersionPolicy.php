<?php

namespace App\Policies;

use App\Models\LessonVersion;
use App\Models\User;

class LessonVersionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->role->canModerate();
    }

    public function create(User $user): bool
    {
        return $user->role->canContribute();
    }

    public function update(
        User $user,
        LessonVersion $version
    ): bool {
        return $user->role->canContribute()
            && $version->created_by === $user->id;
    }

    public function submit(
        User $user,
        LessonVersion $version
    ): bool {
        return $user->role->canContribute()
            && $version->created_by === $user->id;
    }

    public function revise(
        User $user,
        LessonVersion $version
    ): bool {
        return $user->role->canContribute()
            && $version->created_by === $user->id;
    }

    public function review(
        User $user,
        LessonVersion $version
    ): bool {
        return $user->role->canContribute();
    }

    public function viewPedagogicalReviews(
        User $user,
        LessonVersion $version
    ): bool {
        return $user->role->canModerate();
    }

    public function viewPedagogicalConsensus(
        User $user,
        LessonVersion $version
    ): bool {
        return $user->role->canModerate();
    }

    public function approve(
        User $user,
        LessonVersion $version
    ): bool {
        return $user->role->canModerate();
    }

    public function reject(
        User $user,
        LessonVersion $version
    ): bool {
        return $user->role->canModerate();
    }

    public function publish(
        User $user,
        LessonVersion $version
    ): bool {
        return $user->role->canModerate();
    }
}
