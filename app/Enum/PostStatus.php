<?php

namespace App\Enum;

enum PostStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Unpublished = 'unpublished';
    case Archived = 'archived';

    public function isPublicVisible(): bool
    {
        return $this === self::Published;
    }
}
