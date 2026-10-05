<?php

namespace App\Enums;

enum UserRole: string
{
    case Learner = 'learner';
    case Guardian = 'guardian';
    case Teacher = 'teacher';
    case Moderator = 'moderator';
    case Admin = 'admin';

    public function canContribute(): bool
    {
        return in_array($this, [
            self::Teacher,
            self::Moderator,
            self::Admin,
        ], true);
    }

    public function canModerate(): bool
    {
        return in_array($this, [
            self::Moderator,
            self::Admin,
        ], true);
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
