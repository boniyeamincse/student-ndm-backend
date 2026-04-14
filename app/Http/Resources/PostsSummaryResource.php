<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostsSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_posts' => $this['total_posts'] ?? 0,
            'total_drafts' => $this['total_drafts'] ?? 0,
            'total_pending_review' => $this['total_pending_review'] ?? 0,
            'total_published' => $this['total_published'] ?? 0,
            'total_unpublished' => $this['total_unpublished'] ?? 0,
            'total_archived' => $this['total_archived'] ?? 0,
            'total_featured' => $this['total_featured'] ?? 0,
            'published_this_month' => $this['published_this_month'] ?? 0,
            'recent_post_count' => $this['recent_post_count'] ?? 0,
            'by_content_type' => $this['by_content_type'] ?? [],
            'by_visibility' => $this['by_visibility'] ?? [],
            'by_category' => $this['by_category'] ?? [],
        ];
    }
}
