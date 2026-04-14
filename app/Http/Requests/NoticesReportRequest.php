<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoticesReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notice_type'   => ['nullable', 'string', 'max:100'],
            'priority'      => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'status'        => ['nullable', 'string', 'in:draft,pending_review,published,unpublished,archived,expired'],
            'visibility'    => ['nullable', 'string', 'in:public,members_only,committee_specific,private'],
            'audience_type' => ['nullable', 'string', 'in:all,members,committee'],
            'committee_id'  => ['nullable', 'integer', 'exists:committees,id'],
            'is_pinned'     => ['nullable', 'boolean'],
            'start_date'    => ['nullable', 'date'],
            'end_date'      => ['nullable', 'date', 'after_or_equal:start_date'],
            'per_page'      => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'       => ['nullable', 'string', 'in:created_at,published_at,title,priority,status'],
            'sort_dir'      => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
