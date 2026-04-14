<?php

namespace App\Http\Requests;

use App\Enum\ReportingRelationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MySubordinatesListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignment_id' => ['nullable', 'integer', 'exists:committee_member_assignments,id'],
            'active_only' => ['nullable', 'boolean'],
            'relation_type' => ['nullable', new Enum(ReportingRelationType::class)],
            'search' => ['nullable', 'string', 'max:120'],
            'sort_by' => ['nullable', 'in:created_at,member_name'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
