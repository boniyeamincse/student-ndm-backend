<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoticeListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'notice_no' => $this->notice_no,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'notice_type' => $this->notice_type?->value,
            'priority' => $this->priority?->value,
            'status' => $this->status?->value,
            'visibility' => $this->visibility?->value,
            'audience_type' => $this->audience_type?->value,
            'committee' => $this->whenLoaded('committee', fn () => [
                'id' => $this->committee?->id,
                'name' => $this->committee?->name,
                'slug' => $this->committee?->slug,
            ]),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
                'email' => $this->author?->email,
            ]),
            'is_pinned' => $this->is_pinned,
            'publish_at' => $this->publish_at?->toDateTimeString(),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'attachment_count' => $this->attachment_count,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
