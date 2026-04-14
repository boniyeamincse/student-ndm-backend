<?php

namespace App\Http\Requests;

use App\Enum\PostContentType;
use App\Enum\PostVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($id)],
            'excerpt' => ['nullable', 'string'],
            'content' => ['sometimes', 'string'],
            'content_type' => ['sometimes', new Enum(PostContentType::class)],
            'post_category_id' => ['nullable', 'integer', 'exists:post_categories,id'],
            'committee_id' => ['nullable', 'integer', 'exists:committees,id'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'editor_id' => ['nullable', 'integer', 'exists:users,id'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'featured_image_alt' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:60'],
            'visibility' => ['sometimes', new Enum(PostVisibility::class)],
            'is_featured' => ['nullable', 'boolean'],
            'allow_on_homepage' => ['nullable', 'boolean'],
            'scheduled_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
        ];
    }
}
