<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_featured' => ['required', 'boolean'],
            'allow_on_homepage' => ['nullable', 'boolean'],
        ];
    }
}
