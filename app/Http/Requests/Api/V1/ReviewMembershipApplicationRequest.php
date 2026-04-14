<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ReviewMembershipApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('membership.application.review') ?? false;
    }

    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
