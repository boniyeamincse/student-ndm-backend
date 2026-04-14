<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommitteesReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'committee_type_id' => ['nullable', 'integer', 'exists:committee_types,id'],
            'status'            => ['nullable', 'string', 'in:active,inactive,dissolved,archived'],
            'is_current'        => ['nullable', 'boolean'],
            'division_name'     => ['nullable', 'string', 'max:100'],
            'district_name'     => ['nullable', 'string', 'max:100'],
            'parent_id'         => ['nullable', 'integer', 'exists:committees,id'],
            'start_date'        => ['nullable', 'date'],
            'end_date'          => ['nullable', 'date', 'after_or_equal:start_date'],
            'per_page'          => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'           => ['nullable', 'string', 'in:created_at,status,name'],
            'sort_dir'          => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
