<?php

namespace App\Http\Requests;

use App\Enum\MemberStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MemberListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'    => ['nullable', 'string', 'max:100'],
            'status'    => ['nullable', new Enum(MemberStatus::class)],
            'promoted'        => ['nullable', 'boolean'],
            'leadership_only' => ['nullable', 'boolean'],
            'gender'          => ['nullable', 'string', 'in:male,female,other'],
            'division'  => ['nullable', 'string', 'max:100'],
            'district'  => ['nullable', 'string', 'max:100'],
            'upazila'   => ['nullable', 'string', 'max:100'],
            'union_name' => ['nullable', 'string', 'max:100'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'upazila_id' => ['nullable', 'integer', 'exists:upazilas,id'],
            'union_id' => ['nullable', 'integer', 'exists:unions,id'],
            'educational_institution' => ['nullable', 'string', 'max:200'],
            'joined_from' => ['nullable', 'date'],
            'joined_to' => ['nullable', 'date', 'after_or_equal:joined_from'],
            'recent_period_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'sort_by'   => ['nullable', 'string', 'in:member_no,full_name,joined_at,status,created_at'],
            'sort_dir'  => ['nullable', 'string', 'in:asc,desc'],
            'per_page'  => ['nullable', 'integer', 'min:5', 'max:100'],
            'page'      => ['nullable', 'integer', 'min:1'],
        ];
    }
}
