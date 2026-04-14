<?php

namespace App\Enum;

enum NoticeStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Unpublished = 'unpublished';
    case Archived = 'archived';
    case Expired = 'expired';
}
