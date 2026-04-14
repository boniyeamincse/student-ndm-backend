<?php

namespace App\Http\Requests;

use App\Enum\PostContentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PublicPostListRequest extends FormRequest
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
            'category_slug' => ['nullable', 'string', 'max:255'],
            'committee_id' => ['nullable', 'integer', 'exists:committees,id'],
            'featured_only' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'in:published_at,title'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
