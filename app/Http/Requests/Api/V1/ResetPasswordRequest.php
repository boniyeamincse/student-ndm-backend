<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'                 => ['required', 'string'],
            'email'                 => ['required', 'string', 'email'],
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
            'token.required'    => 'Reset token is required.',
            'email.required'    => 'Email address is required.',
            'password.required' => 'New password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
