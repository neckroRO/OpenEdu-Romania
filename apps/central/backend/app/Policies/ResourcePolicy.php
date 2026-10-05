<?php

namespace App\Policies;

use App\Models\Resource;
use App\Models\User;

class ResourcePolicy
{
    public function create(User $user): bool
    {
        return $user->role->canContribute();
    }
}
