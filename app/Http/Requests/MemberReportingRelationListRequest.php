<?php

namespace App\Http\Requests;

use App\Enum\ReportingRelationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MemberReportingRelationListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'committee_id' => ['nullable', 'integer', 'exists:committees,id'],
            'committee_type_id' => ['nullable', 'integer', 'exists:committee_types,id'],
            'subordinate_assignment_id' => ['nullable', 'integer', 'exists:committee_member_assignments,id'],
            'superior_assignment_id' => ['nullable', 'integer', 'exists:committee_member_assignments,id'],
            'relation_type' => ['nullable', new Enum(ReportingRelationType::class)],
            'is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'start_date_from' => ['nullable', 'date'],
            'start_date_to' => ['nullable', 'date', 'after_or_equal:start_date_from'],
            'end_date_from' => ['nullable', 'date'],
            'end_date_to' => ['nullable', 'date', 'after_or_equal:end_date_from'],
            'sort_by' => ['nullable', 'in:created_at,start_date,subordinate_name,superior_name,relation_type'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
