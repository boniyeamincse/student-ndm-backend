<?php

namespace App\Http\Requests;

use App\Enum\AssignmentStatus;
use App\Enum\AssignmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CommitteeMembersListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'assignment_type' => ['nullable', new Enum(AssignmentType::class)],
            'status' => ['nullable', new Enum(AssignmentStatus::class)],
            'is_active' => ['nullable', 'boolean'],
            'is_leadership' => ['nullable', 'boolean'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'sort_by' => ['nullable', 'string', 'in:position_rank,member_name,assigned_at'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
