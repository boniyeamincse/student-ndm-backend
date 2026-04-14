<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MembershipApplicationsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'        => ['nullable', 'string', 'in:pending,under_review,approved,rejected,on_hold'],
            'division_name' => ['nullable', 'string', 'max:100'],
            'district_name' => ['nullable', 'string', 'max:100'],
            'start_date'    => ['nullable', 'date'],
            'end_date'      => ['nullable', 'date', 'after_or_equal:start_date'],
            'source'        => ['nullable', 'string', 'max:100'],
            'reviewer_id'   => ['nullable', 'integer', 'exists:users,id'],
            'approved_by'   => ['nullable', 'integer', 'exists:users,id'],
            'per_page'      => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'       => ['nullable', 'string', 'in:created_at,status,division_name,district_name'],
            'sort_dir'      => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
