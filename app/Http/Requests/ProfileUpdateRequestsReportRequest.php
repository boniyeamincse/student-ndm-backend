<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequestsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type' => ['nullable', 'string', 'max:100'],
            'status'       => ['nullable', 'string', 'in:pending,approved,rejected,cancelled'],
            'user_id'      => ['nullable', 'integer', 'exists:users,id'],
            'member_id'    => ['nullable', 'integer', 'exists:members,id'],
            'reviewed_by'  => ['nullable', 'integer', 'exists:users,id'],
            'start_date'   => ['nullable', 'date'],
            'end_date'     => ['nullable', 'date', 'after_or_equal:start_date'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'      => ['nullable', 'string', 'in:created_at,status,request_type'],
            'sort_dir'     => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
