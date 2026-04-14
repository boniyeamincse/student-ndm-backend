<?php

namespace App\Http\Requests;

use App\Enum\MemberStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateMemberStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'         => ['required', new Enum(MemberStatus::class)],
            'note'           => ['nullable', 'string', 'max:500'],
            'revoke_access'  => ['nullable', 'boolean'],
        ];
    }
}
