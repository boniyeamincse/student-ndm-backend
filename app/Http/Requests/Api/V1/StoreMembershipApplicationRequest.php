<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public endpoint — anyone can submit
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name'                => ['required', 'string', 'max:255'],
            'mobile'                   => ['nullable', 'string', 'max:20'],
            'email'                    => ['nullable', 'email', 'max:255'],
            'gender'                   => ['nullable', 'in:male,female,other'],
            'date_of_birth'            => ['nullable', 'date', 'before:today'],
            'father_name'              => ['nullable', 'string', 'max:255'],
            'mother_name'              => ['nullable', 'string', 'max:255'],
            'national_id'              => ['nullable', 'string', 'max:50'],
            'student_id'               => ['nullable', 'string', 'max:50'],
            'blood_group'              => ['nullable', 'string', 'max:5'],
            'educational_institution'  => ['nullable', 'string', 'max:255'],
            'department'               => ['nullable', 'string', 'max:255'],
            'academic_year'            => ['nullable', 'string', 'max:20'],
            'occupation'               => ['nullable', 'string', 'max:255'],
            'address_line'             => ['nullable', 'string', 'max:500'],
            'village_area'             => ['nullable', 'string', 'max:255'],
            'post_office'              => ['nullable', 'string', 'max:255'],
            'union_name'               => ['nullable', 'string', 'max:255'],
            'upazila_name'             => ['nullable', 'string', 'max:255'],
            'district_name'            => ['nullable', 'string', 'max:255'],
            'division_name'            => ['nullable', 'string', 'max:255'],
            'emergency_contact_name'   => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone'  => ['nullable', 'string', 'max:20'],
            'reference_member_id'      => ['nullable', 'integer', 'exists:members,id'],
            'desired_committee_level'  => ['nullable', 'string', 'max:100'],
            'desired_committee_id'     => ['nullable', 'integer'],
            'motivation'               => ['nullable', 'string', 'max:2000'],
            // Photo: optional, validated strictly
            'photo'                    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * At least one of mobile or email must be present.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (empty($this->mobile) && empty($this->email)) {
                $v->errors()->add('contact', 'At least one of mobile or email is required.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Full name is required.',
            'email.email'        => 'Please enter a valid email address.',
            'photo.image'        => 'Photo must be an image file (jpg, jpeg, png, webp).',
            'photo.max'          => 'Photo file size must not exceed 2MB.',
        ];
    }
}
