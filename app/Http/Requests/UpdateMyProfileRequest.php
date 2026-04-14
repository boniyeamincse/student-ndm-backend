<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (int) $this->user()->id;
        $memberId = (int) optional($this->user()->member)->id;

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($userId),
                Rule::unique('members', 'mobile')->ignore($memberId),
            ],
            'address_line' => ['nullable', 'string', 'max:500'],
            'village_area' => ['nullable', 'string', 'max:255'],
            'post_office' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'email' => ['nullable', 'email', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'educational_institution' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'occupation' => ['nullable', 'string', 'max:255'],
        ];
    }
}
