<?php

namespace App\Http\Requests;

use App\Enum\ReportingRelationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateMemberReportingRelationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'superior_assignment_id' => ['sometimes', 'integer', 'exists:committee_member_assignments,id'],
            'committee_id' => ['nullable', 'integer', 'exists:committees,id'],
            'relation_type' => ['sometimes', new Enum(ReportingRelationType::class)],
            'is_primary' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
