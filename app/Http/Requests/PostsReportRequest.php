<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_type'     => ['nullable', 'string', 'in:blog,news,announcement,press_release'],
            'post_category_id' => ['nullable', 'integer', 'exists:post_categories,id'],
            'author_id'        => ['nullable', 'integer', 'exists:users,id'],
            'status'           => ['nullable', 'string', 'in:draft,pending_review,published,unpublished,archived'],
            'visibility'       => ['nullable', 'string', 'in:public,members_only,private'],
            'is_featured'      => ['nullable', 'boolean'],
            'start_date'       => ['nullable', 'date'],
            'end_date'         => ['nullable', 'date', 'after_or_equal:start_date'],
            'per_page'         => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'          => ['nullable', 'string', 'in:created_at,published_at,title,status'],
            'sort_dir'         => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
