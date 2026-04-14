<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommitteeAssignmentsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'committee_id'      => ['nullable', 'integer', 'exists:committees,id'],
            'committee_type_id' => ['nullable', 'integer', 'exists:committee_types,id'],
            'member_id'         => ['nullable', 'integer', 'exists:members,id'],
            'position_id'       => ['nullable', 'integer', 'exists:positions,id'],
            'assignment_type'   => ['nullable', 'string', 'in:regular,leadership,office_bearer,advisor,observer'],
            'is_primary'        => ['nullable', 'boolean'],
            'is_leadership'     => ['nullable', 'boolean'],
            'status'            => ['nullable', 'string', 'in:active,inactive,completed,removed'],
            'active_only'       => ['nullable', 'boolean'],
            'start_date'        => ['nullable', 'date'],
            'end_date'          => ['nullable', 'date', 'after_or_equal:start_date'],
            'per_page'          => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'           => ['nullable', 'string', 'in:created_at,status'],
            'sort_dir'          => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
