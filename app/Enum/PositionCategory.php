<?php

namespace App\Enum;

enum PositionCategory: string
{
    case Leadership = 'leadership';
    case Executive = 'executive';
    case General = 'general';
    case Advisory = 'advisory';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Leadership => 'Leadership',
            self::Executive => 'Executive',
            self::General => 'General',
            self::Advisory => 'Advisory',
            self::Other => 'Other',
        };
    }
}
