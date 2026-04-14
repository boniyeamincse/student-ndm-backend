<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RejectMembershipApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('membership.application.reject') ?? false;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'A rejection reason is required.',
        ];
    }
}
