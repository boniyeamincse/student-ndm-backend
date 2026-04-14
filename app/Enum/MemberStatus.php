<?php

namespace App\Enum;

enum MemberStatus: string
{
    case Active    = 'active';
    case Inactive  = 'inactive';
    case Suspended = 'suspended';
    case Resigned  = 'resigned';
    case Removed   = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::Active    => 'Active',
            self::Inactive  => 'Inactive',
            self::Suspended => 'Suspended',
            self::Resigned  => 'Resigned',
            self::Removed   => 'Removed',
        };
    }
}
