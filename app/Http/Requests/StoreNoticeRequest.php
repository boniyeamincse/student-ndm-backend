<?php

namespace App\Http\Requests;

use App\Enum\NoticeAudienceRuleType;
use App\Enum\NoticeAudienceType;
use App\Enum\NoticePriority;
use App\Enum\NoticeStatus;
use App\Enum\NoticeType;
use App\Enum\NoticeVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('status')) {
            $this->merge(['status' => NoticeStatus::Draft->value]);
        }
        if (! $this->has('is_pinned')) {
            $this->merge(['is_pinned' => false]);
        }
        if (! $this->has('requires_acknowledgement')) {
            $this->merge(['requires_acknowledgement' => false]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:notices,slug'],
            'summary' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'notice_type' => ['required', new Enum(NoticeType::class)],
            'priority' => ['required', new Enum(NoticePriority::class)],
            'status' => ['nullable', new Enum(NoticeStatus::class)],
            'visibility' => ['required', new Enum(NoticeVisibility::class)],
            'audience_type' => ['required', new Enum(NoticeAudienceType::class)],
            'committee_id' => ['nullable', 'integer', 'exists:committees,id'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'approver_id' => ['nullable', 'integer', 'exists:users,id'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'featured_image_alt' => ['nullable', 'string', 'max:255'],
            'publish_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:publish_at'],
            'is_pinned' => ['nullable', 'boolean'],
            'requires_acknowledgement' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,csv,zip', 'max:8192'],
            'audience_rules' => ['nullable', 'array'],
            'audience_rules.*.rule_type' => ['required_with:audience_rules', new Enum(NoticeAudienceRuleType::class)],
            'audience_rules.*.rule_value' => ['required_with:audience_rules', 'string', 'max:255'],
        ];
    }
}
