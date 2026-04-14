<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $memberId = $this->route('member');

        return [
            'full_name'              => ['required', 'string', 'max:150'],
            'email'                  => ['nullable', 'email', 'max:150', Rule::unique('members', 'email')->ignore($memberId)],
            'mobile'                 => ['nullable', 'string', 'max:20', Rule::unique('members', 'mobile')->ignore($memberId)],
            'photo'                  => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'gender'                 => ['nullable', 'string', 'in:male,female,other'],
            'date_of_birth'          => ['nullable', 'date', 'before:today'],
            'blood_group'            => ['nullable', 'string', 'max:5'],
            'bio'                    => ['nullable', 'string', 'max:1000'],
            'father_name'            => ['nullable', 'string', 'max:150'],
            'mother_name'            => ['nullable', 'string', 'max:150'],
            'educational_institution' => ['nullable', 'string', 'max:200'],
            'department'             => ['nullable', 'string', 'max:150'],
            'academic_year'          => ['nullable', 'string', 'max:20'],
            'occupation'             => ['nullable', 'string', 'max:150'],
            'address_line'           => ['nullable', 'string', 'max:255'],
            'village_area'           => ['nullable', 'string', 'max:150'],
            'post_office'            => ['nullable', 'string', 'max:100'],
            'union_name'             => ['nullable', 'string', 'max:100'],
            'upazila_name'           => ['nullable', 'string', 'max:100'],
            'district_name'          => ['nullable', 'string', 'max:100'],
            'division_name'          => ['nullable', 'string', 'max:100'],
            'emergency_contact_name'  => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
