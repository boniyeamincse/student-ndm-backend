<?php

namespace App\Http\Requests;

use App\Enum\AssignmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MemberCommitteeAssignmentsListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', new Enum(AssignmentStatus::class)],
            'is_active' => ['nullable', 'boolean'],
            'committee_type_id' => ['nullable', 'integer', 'exists:committee_types,id'],
            'leadership_only' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'string', 'in:created_at,assigned_at,start_date,position_rank'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
