<?php

namespace App\Http\Requests;

use App\Enum\NoticePriority;
use App\Enum\NoticeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PublicNoticeListRequest extends FormRequest
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
            'committee_id' => ['nullable', 'integer', 'exists:committees,id'],
            'pinned_only' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'in:publish_at,priority,title'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
