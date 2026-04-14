<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicPostDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'content_type' => $this->content_type?->value,
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ],
            'author_name' => $this->author?->name,
            'featured_image_url' => $this->featured_image_url,
            'featured_image_alt' => $this->featured_image_alt,
            'published_at' => $this->published_at?->toDateTimeString(),
            'tags' => $this->tags ?? [],
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'committee' => [
                'id' => $this->committee?->id,
                'name' => $this->committee?->name,
                'slug' => $this->committee?->slug,
            ],
            'view_count' => $this->view_count,
        ];
    }
}
