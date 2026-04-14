<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicPostListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content_type' => $this->content_type?->value,
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ],
            'author_name' => $this->author?->name,
            'featured_image_url' => $this->featured_image_url,
            'published_at' => $this->published_at?->toDateTimeString(),
            'tags' => $this->tags ?? [],
            'view_count' => $this->view_count,
            'committee' => [
                'id' => $this->committee?->id,
                'name' => $this->committee?->name,
                'slug' => $this->committee?->slug,
            ],
        ];
    }
}
