<?php

namespace App\Http\Requests;

use App\Enum\AssignmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCommitteeMemberAssignmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(AssignmentStatus::class)],
            'is_active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
