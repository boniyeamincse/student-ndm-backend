<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoticesSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_notices' => $this['total_notices'] ?? 0,
            'total_drafts' => $this['total_drafts'] ?? 0,
            'total_pending_review' => $this['total_pending_review'] ?? 0,
            'total_published' => $this['total_published'] ?? 0,
            'total_unpublished' => $this['total_unpublished'] ?? 0,
            'total_archived' => $this['total_archived'] ?? 0,
            'total_expired' => $this['total_expired'] ?? 0,
            'total_pinned' => $this['total_pinned'] ?? 0,
            'total_requires_acknowledgement' => $this['total_requires_acknowledgement'] ?? 0,
            'notices_expiring_soon' => $this['notices_expiring_soon'] ?? 0,
            'published_this_month' => $this['published_this_month'] ?? 0,
            'by_notice_type' => $this['by_notice_type'] ?? [],
            'by_priority' => $this['by_priority'] ?? [],
            'by_visibility' => $this['by_visibility'] ?? [],
            'by_audience_type' => $this['by_audience_type'] ?? [],
            'by_committee' => $this['by_committee'] ?? [],
        ];
    }
}
