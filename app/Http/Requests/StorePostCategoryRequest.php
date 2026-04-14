<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:post_categories,name'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:post_categories,slug'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
