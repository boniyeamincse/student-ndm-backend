<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Accept email or phone as the login identifier
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required'    => 'Email or phone number is required.',
            'password.required' => 'Password is required.',
        ];
    }

    /**
     * Determine if the login field is an email or phone.
     */
    public function loginField(): string
    {
        return filter_var($this->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
    }
}
