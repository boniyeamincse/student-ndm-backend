<?php

namespace App\Http\Requests;

use App\Enum\NoticeAudienceType;
use App\Enum\NoticePriority;
use App\Enum\NoticeStatus;
use App\Enum\NoticeType;
use App\Enum\NoticeVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class NoticeListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'notice_type' => ['nullable', new Enum(NoticeType::class)],
            'priority' => ['nullable', new Enum(NoticePriority::class)],
            'status' => ['nullable', new Enum(NoticeStatus::class)],
            'visibility' => ['nullable', new Enum(NoticeVisibility::class)],
            'audience_type' => ['nullable', new Enum(NoticeAudienceType::class)],
            'committee_id' => ['nullable', 'integer', 'exists:committees,id'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'approver_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_pinned' => ['nullable', 'boolean'],
            'requires_acknowledgement' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'publish_from' => ['nullable', 'date'],
            'publish_to' => ['nullable', 'date', 'after_or_equal:publish_from'],
            'expires_from' => ['nullable', 'date'],
            'expires_to' => ['nullable', 'date', 'after_or_equal:expires_from'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
            'sort_by' => ['nullable', 'in:created_at,publish_at,priority,title,expires_at'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
