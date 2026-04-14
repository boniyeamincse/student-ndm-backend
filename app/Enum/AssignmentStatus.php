<?php

namespace App\Enum;

enum AssignmentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Completed = 'completed';
    case Removed = 'removed';

    public function shouldBeActiveFlag(): bool
    {
        return $this === self::Active;
    }
}
