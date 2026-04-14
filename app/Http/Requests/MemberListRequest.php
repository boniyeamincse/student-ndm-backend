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
            'gender'    => ['nullable', 'string', 'in:male,female,other'],
            'division'  => ['nullable', 'string', 'max:100'],
            'district'  => ['nullable', 'string', 'max:100'],
            'upazila'   => ['nullable', 'string', 'max:100'],
            'sort_by'   => ['nullable', 'string', 'in:member_no,full_name,joined_at,status,created_at'],
            'sort_dir'  => ['nullable', 'string', 'in:asc,desc'],
            'per_page'  => ['nullable', 'integer', 'min:5', 'max:100'],
            'page'      => ['nullable', 'integer', 'min:1'],
        ];
    }
}
