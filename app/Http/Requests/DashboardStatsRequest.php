<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardStatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scope'          => ['nullable', 'string', 'in:current_month,current_year,all_time'],
            'include_growth' => ['nullable', 'boolean'],
        ];
    }
}
