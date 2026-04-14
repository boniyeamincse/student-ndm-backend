<?php

namespace App\Enum;

enum CommitteeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Dissolved = 'dissolved';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Dissolved => 'Dissolved',
            self::Archived => 'Archived',
        };
    }

    public function isCurrentDefault(): bool
    {
        return $this === self::Active || $this === self::Inactive;
    }
}
