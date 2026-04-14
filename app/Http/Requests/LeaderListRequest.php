<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaderListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'include_non_primary' => ['nullable', 'boolean'],
            'include_all_relation_types' => ['nullable', 'boolean'],
        ];
    }
}
