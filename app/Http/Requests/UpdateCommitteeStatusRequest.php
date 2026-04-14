<?php

namespace App\Http\Requests;

use App\Enum\CommitteeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCommitteeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(CommitteeStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
