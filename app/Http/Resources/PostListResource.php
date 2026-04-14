<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostListResource extends JsonResource
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
            'status' => $this->status?->value,
            'visibility' => $this->visibility?->value,
            'is_featured' => $this->is_featured,
            'allow_on_homepage' => $this->allow_on_homepage,
            'published_at' => $this->published_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'featured_image_url' => $this->featured_image_url,
        ];
    }
}
