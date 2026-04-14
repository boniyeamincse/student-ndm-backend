<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'post_no' => $this->post_no,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'content_type' => $this->content_type?->value,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ]),
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
            'editor' => $this->whenLoaded('editor', fn () => [
                'id' => $this->editor?->id,
                'name' => $this->editor?->name,
                'email' => $this->editor?->email,
            ]),
            'featured_image_url' => $this->featured_image_url,
            'featured_image_alt' => $this->featured_image_alt,
            'tags' => $this->tags ?? [],
            'status' => $this->status?->value,
            'visibility' => $this->visibility?->value,
            'is_featured' => $this->is_featured,
            'allow_on_homepage' => $this->allow_on_homepage,
            'published_at' => $this->published_at?->toDateTimeString(),
            'scheduled_at' => $this->scheduled_at?->toDateTimeString(),
            'last_edited_at' => $this->last_edited_at?->toDateTimeString(),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'view_count' => $this->view_count,
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
            'status_history_timeline' => PostStatusHistoryResource::collection($this->whenLoaded('statusHistories')),

            // Future placeholder: related_posts
            // Future placeholder: attachments
            // Future placeholder: seo_analysis
            // Future placeholder: translations
        ];
    }
}
