<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportsOverviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', 'string', 'in:7d,30d,90d,12m,all_time'],
        ];
    }
}
