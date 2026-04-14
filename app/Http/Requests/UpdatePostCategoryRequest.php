<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('post_categories', 'name')->ignore($id)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('post_categories', 'slug')->ignore($id)],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
