<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Current password is required.',
            'password.required'         => 'New password is required.',
            'password.confirmed'        => 'Password confirmation does not match.',
        ];
    }
}
