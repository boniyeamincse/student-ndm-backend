<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MembersReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'                  => ['nullable', 'string', 'in:active,inactive,suspended,resigned,removed'],
            'gender'                  => ['nullable', 'string', 'in:male,female,other'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'upazila_id' => ['nullable', 'integer', 'exists:upazilas,id'],
            'union_id' => ['nullable', 'integer', 'exists:unions,id'],
            'educational_institution' => ['nullable', 'string', 'max:255'],
            'joined_at_from'          => ['nullable', 'date'],
            'joined_at_to'            => ['nullable', 'date', 'after_or_equal:joined_at_from'],
            'group_by'                => ['nullable', 'string', 'in:division,district,institution,status,gender'],
            'per_page'                => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'                 => ['nullable', 'string', 'in:created_at,full_name,joined_at,status'],
            'sort_dir'                => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
