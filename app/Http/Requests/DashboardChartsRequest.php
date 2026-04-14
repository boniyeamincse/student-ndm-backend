<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardChartsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period'                      => ['nullable', 'string', 'in:7d,30d,90d,12m,custom'],
            'start_date'                  => ['nullable', 'date'],
            'end_date'                    => ['nullable', 'date', 'after_or_equal:start_date'],
            'include_membership_trend'    => ['nullable', 'boolean'],
            'include_member_growth'       => ['nullable', 'boolean'],
            'include_content_summary'     => ['nullable', 'boolean'],
        ];
    }
}
