<?php

namespace App\Http\Requests;

use App\Enum\NoticeAudienceRuleType;
use App\Enum\NoticeAudienceType;
use App\Enum\NoticePriority;
use App\Enum\NoticeType;
use App\Enum\NoticeVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateNoticeRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('notices', 'slug')->ignore($id)],
            'summary' => ['nullable', 'string'],
            'content' => ['sometimes', 'string'],
            'notice_type' => ['sometimes', new Enum(NoticeType::class)],
            'priority' => ['sometimes', new Enum(NoticePriority::class)],
            'visibility' => ['sometimes', new Enum(NoticeVisibility::class)],
            'audience_type' => ['sometimes', new Enum(NoticeAudienceType::class)],
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
            'audience_rules' => ['nullable', 'array'],
            'audience_rules.*.rule_type' => ['required_with:audience_rules', new Enum(NoticeAudienceRuleType::class)],
            'audience_rules.*.rule_value' => ['required_with:audience_rules', 'string', 'max:255'],
        ];
    }
}
