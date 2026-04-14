<?php

namespace App\Enum;

enum PostVisibility: string
{
    case Public = 'public';
    case MembersOnly = 'members_only';
    case Internal = 'internal';

    public function isPublic(): bool
    {
        return $this === self::Public;
    }
}
