<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommitteeOfficeBearersListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'include_inactive' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
