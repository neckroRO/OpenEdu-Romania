<?php

namespace App\Policies;

use App\Models\ResourceVersion;
use App\Models\User;

class ResourceVersionPolicy
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

    public function update(
        User $user,
        ResourceVersion $version
    ): bool {
        return $user->role->canContribute()
            && $version->created_by === $user->id;
    }

    public function submit(
        User $user,
        ResourceVersion $version
    ): bool {
        return $user->role->canContribute()
            && $version->created_by === $user->id;
    }

    public function revise(
        User $user,
        ResourceVersion $version
    ): bool {
        return $user->role->canContribute()
            && $version->created_by === $user->id;
    }

    public function approve(
        User $user,
        ResourceVersion $version
    ): bool {
        return $user->role->canModerate();
    }

    public function reject(
        User $user,
        ResourceVersion $version
    ): bool {
        return $user->role->canModerate();
    }

    public function publish(
        User $user,
        ResourceVersion $version
    ): bool {
        return $user->role->canModerate();
    }
}
