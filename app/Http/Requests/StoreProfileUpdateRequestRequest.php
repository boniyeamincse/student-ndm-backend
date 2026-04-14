<?php

namespace App\Http\Requests;

use App\Enum\ProfileUpdateRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProfileUpdateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type' => ['required', new Enum(ProfileUpdateRequestType::class)],
            'requested_changes' => ['required', 'array', 'min:1'],
            'submitted_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
