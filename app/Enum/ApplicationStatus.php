<?php

namespace App\Enum;

enum ApplicationStatus: string
{
    case Pending     = 'pending';
    case UnderReview = 'under_review';
    case Approved    = 'approved';
    case Rejected    = 'rejected';
    case OnHold      = 'on_hold';

    public function label(): string
    {
        return match ($this) {
            self::Pending     => 'Pending',
            self::UnderReview => 'Under Review',
            self::Approved    => 'Approved',
            self::Rejected    => 'Rejected',
            self::OnHold      => 'On Hold',
        };
    }

    /** Returns statuses that are considered still actionable (not finalized). */
    public function isActionable(): bool
    {
        return in_array($this, [self::Pending, self::UnderReview, self::OnHold], true);
    }

    public function isFinalized(): bool
    {
        return $this === self::Approved || $this === self::Rejected;
    }
}
