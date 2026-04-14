<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicNoticeDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'content' => $this->content,
            'notice_type' => $this->notice_type?->value,
            'priority' => $this->priority?->value,
            'featured_image_url' => $this->featured_image_url,
            'featured_image_alt' => $this->featured_image_alt,
            'publish_at' => $this->publish_at?->toDateTimeString(),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'committee' => [
                'id' => $this->committee?->id,
                'name' => $this->committee?->name,
                'slug' => $this->committee?->slug,
            ],
            'attachments' => NoticeAttachmentResource::collection($this->whenLoaded('attachments')),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
        ];
    }
}
