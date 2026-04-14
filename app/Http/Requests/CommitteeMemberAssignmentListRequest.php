<?php

namespace App\Http\Requests;

use App\Enum\AssignmentStatus;
use App\Enum\AssignmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CommitteeMemberAssignmentListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'committee_id' => ['nullable', 'integer', 'exists:committees,id'],
            'committee_type_id' => ['nullable', 'integer', 'exists:committee_types,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'assignment_type' => ['nullable', new Enum(AssignmentType::class)],
            'status' => ['nullable', new Enum(AssignmentStatus::class)],
            'is_active' => ['nullable', 'boolean'],
            'is_primary' => ['nullable', 'boolean'],
            'is_leadership' => ['nullable', 'boolean'],
            'division_name' => ['nullable', 'string', 'max:100'],
            'district_name' => ['nullable', 'string', 'max:100'],
            'upazila_name' => ['nullable', 'string', 'max:100'],
            'union_name' => ['nullable', 'string', 'max:100'],
            'start_date_from' => ['nullable', 'date'],
            'start_date_to' => ['nullable', 'date'],
            'end_date_from' => ['nullable', 'date'],
            'end_date_to' => ['nullable', 'date'],
            'sort_by' => ['nullable', 'string', 'in:created_at,assigned_at,start_date,end_date,status,member_name,committee_name,position_rank'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
