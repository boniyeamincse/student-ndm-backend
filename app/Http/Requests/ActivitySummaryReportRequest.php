<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivitySummaryReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module'     => ['nullable', 'string', 'in:applications,members,committees,assignments,hierarchy,posts,notices,profile_requests'],
            'actor_id'   => ['nullable', 'integer', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
            'limit'      => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
