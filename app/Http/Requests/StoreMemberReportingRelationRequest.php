<?php

namespace App\Http\Requests;

use App\Enum\ReportingRelationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreMemberReportingRelationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('relation_type')) {
            $this->merge(['relation_type' => ReportingRelationType::DirectReport->value]);
        }
        if (! $this->has('is_primary')) {
            $this->merge(['is_primary' => true]);
        }
        if (! $this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }

    public function rules(): array
    {
        return [
            'subordinate_assignment_id' => ['required', 'integer', 'exists:committee_member_assignments,id'],
            'superior_assignment_id' => ['required', 'integer', 'exists:committee_member_assignments,id'],
            'committee_id' => ['nullable', 'integer', 'exists:committees,id'],
            'relation_type' => ['required', new Enum(ReportingRelationType::class)],
            'is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
