<?php

namespace App\Http\Requests;

use App\Enum\PostContentType;
use App\Enum\PostStatus;
use App\Enum\PostVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('status')) {
            $this->merge(['status' => PostStatus::Draft->value]);
        }
        if (! $this->has('is_featured')) {
            $this->merge(['is_featured' => false]);
        }
        if (! $this->has('allow_on_homepage')) {
            $this->merge(['allow_on_homepage' => false]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts,slug'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'content_type' => ['required', new Enum(PostContentType::class)],
            'post_category_id' => ['nullable', 'integer', 'exists:post_categories,id'],
            'committee_id' => ['nullable', 'integer', 'exists:committees,id'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'editor_id' => ['nullable', 'integer', 'exists:users,id'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'featured_image_alt' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:60'],
            'status' => ['nullable', new Enum(PostStatus::class)],
            'visibility' => ['required', new Enum(PostVisibility::class)],
            'is_featured' => ['nullable', 'boolean'],
            'allow_on_homepage' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
        ];
    }
}
