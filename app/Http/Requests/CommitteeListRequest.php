<?php

namespace App\Http\Requests;

use App\Enum\CommitteeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CommitteeListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'committee_type_id' => ['nullable', 'integer', 'exists:committee_types,id'],
            'status' => ['nullable', new Enum(CommitteeStatus::class)],
            'is_current' => ['nullable', 'boolean'],
            'division_name' => ['nullable', 'string', 'max:100'],
            'district_name' => ['nullable', 'string', 'max:100'],
            'upazila_name' => ['nullable', 'string', 'max:100'],
            'union_name' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:committees,id'],
            'sort_by' => ['nullable', 'string', 'in:created_at,name,start_date,hierarchy_order,status'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
