<?php

namespace App\Enum;

enum PositionScope: string
{
    case Global = 'global';
    case CommitteeSpecific = 'committee_specific';

    public function label(): string
    {
        return match ($this) {
            self::Global => 'Global',
            self::CommitteeSpecific => 'Committee Specific',
        };
    }
}
