<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportingHierarchyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'committee_id'      => ['nullable', 'integer', 'exists:committees,id'],
            'committee_type_id' => ['nullable', 'integer', 'exists:committee_types,id'],
            'relation_type'     => ['nullable', 'string', 'in:direct,functional,advisory'],
            'is_primary'        => ['nullable', 'boolean'],
            'is_active'         => ['nullable', 'boolean'],
            'per_page'          => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'           => ['nullable', 'string', 'in:created_at'],
            'sort_dir'          => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
