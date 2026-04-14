<?php

namespace App\Http\Requests;

use App\Enum\PostContentType;
use App\Enum\PostStatus;
use App\Enum\PostVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PostListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'content_type' => ['nullable', new Enum(PostContentType::class)],
            'post_category_id' => ['nullable', 'integer', 'exists:post_categories,id'],
            'committee_id' => ['nullable', 'integer', 'exists:committees,id'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'editor_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', new Enum(PostStatus::class)],
            'visibility' => ['nullable', new Enum(PostVisibility::class)],
            'is_featured' => ['nullable', 'boolean'],
            'allow_on_homepage' => ['nullable', 'boolean'],
            'published_from' => ['nullable', 'date'],
            'published_to' => ['nullable', 'date', 'after_or_equal:published_from'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
            'sort_by' => ['nullable', 'in:created_at,published_at,title,status'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
