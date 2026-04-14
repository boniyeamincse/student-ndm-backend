<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicNoticeListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'notice_type' => $this->notice_type?->value,
            'priority' => $this->priority?->value,
            'featured_image_url' => $this->featured_image_url,
            'publish_at' => $this->publish_at?->toDateTimeString(),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'is_pinned' => $this->is_pinned,
            'committee' => [
                'id' => $this->committee?->id,
                'name' => $this->committee?->name,
                'slug' => $this->committee?->slug,
            ],
            'has_attachments' => $this->attachment_count > 0,
            'attachment_count' => $this->attachment_count,
        ];
    }
}
