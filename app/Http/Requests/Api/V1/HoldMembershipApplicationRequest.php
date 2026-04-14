<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class HoldMembershipApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('membership.application.hold') ?? false;
    }

    public function rules(): array
    {
        return [
            'remarks' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'remarks.required' => 'A reason or remark is required when placing an application on hold.',
        ];
    }
}
