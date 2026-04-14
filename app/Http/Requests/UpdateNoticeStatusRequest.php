<?php

namespace App\Http\Requests;

use App\Enum\NoticeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateNoticeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(NoticeStatus::class)],
            'note' => ['nullable', 'string', 'max:1500'],
        ];
    }
}
