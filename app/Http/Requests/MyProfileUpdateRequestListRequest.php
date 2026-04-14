<?php

namespace App\Http\Requests;

use App\Enum\ProfileUpdateRequestStatus;
use App\Enum\ProfileUpdateRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MyProfileUpdateRequestListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', new Enum(ProfileUpdateRequestStatus::class)],
            'request_type' => ['nullable', new Enum(ProfileUpdateRequestType::class)],
            'sort_by' => ['nullable', 'in:created_at,status'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
