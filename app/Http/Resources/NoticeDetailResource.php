<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoticeDetailResource extends JsonResource
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
            'content' => $this->content,
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
            'approver' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver?->id,
                'name' => $this->approver?->name,
                'email' => $this->approver?->email,
            ]),
            'featured_image_url' => $this->featured_image_url,
            'featured_image_alt' => $this->featured_image_alt,
            'publish_at' => $this->publish_at?->toDateTimeString(),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'is_pinned' => $this->is_pinned,
            'requires_acknowledgement' => $this->requires_acknowledgement,
            'attachment_count' => $this->attachment_count,
            'attachments' => NoticeAttachmentResource::collection($this->whenLoaded('attachments')),
            'audience_rules' => $this->whenLoaded('audienceRules', fn () => $this->audienceRules->map(fn ($rule) => [
                'id' => $rule->id,
                'rule_type' => $rule->rule_type?->value,
                'rule_value' => $rule->rule_value,
                'created_at' => $rule->created_at?->toDateTimeString(),
            ])),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'last_edited_at' => $this->last_edited_at?->toDateTimeString(),
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
                'email' => $this->creator?->email,
            ]),
            'updated_by' => $this->whenLoaded('updater', fn () => [
                'id' => $this->updater?->id,
                'name' => $this->updater?->name,
                'email' => $this->updater?->email,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'status_history_timeline' => NoticeStatusHistoryResource::collection($this->whenLoaded('statusHistories')),

            // Future placeholder: acknowledgement_stats
            // Future placeholder: recipient_counts
            // Future placeholder: send_logs
        ];
    }
}
