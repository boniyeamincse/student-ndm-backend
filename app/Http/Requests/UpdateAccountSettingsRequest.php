<?php

namespace App\Http\Requests;

use App\Enum\ProfileVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAccountSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'language' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'timezone:all'],
            'notification_email_enabled' => ['nullable', 'boolean'],
            'notification_sms_enabled' => ['nullable', 'boolean'],
            'notification_push_enabled' => ['nullable', 'boolean'],
            'profile_visibility' => ['nullable', new Enum(ProfileVisibility::class)],
            'show_email' => ['nullable', 'boolean'],
            'show_phone' => ['nullable', 'boolean'],
            'show_address' => ['nullable', 'boolean'],
        ];
    }
}
