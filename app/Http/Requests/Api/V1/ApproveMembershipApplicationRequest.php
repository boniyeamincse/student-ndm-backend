<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ApproveMembershipApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('membership.application.approve') ?? false;
    }

    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
