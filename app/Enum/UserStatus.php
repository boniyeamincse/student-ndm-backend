<?php

namespace App\Enum;

enum UserStatus: string
{
    case Active    = 'active';
    case Inactive  = 'inactive';
    case Suspended = 'suspended';
    case Pending   = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Active    => 'Active',
            self::Inactive  => 'Inactive',
            self::Suspended => 'Suspended',
            self::Pending   => 'Pending Approval',
        };
    }

    public function canLogin(): bool
    {
        return $this === self::Active;
    }
}
