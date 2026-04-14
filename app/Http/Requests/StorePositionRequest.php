<?php

namespace App\Http\Requests;

use App\Enum\PositionCategory;
use App\Enum\PositionScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if ($this->filled('name') && ! $this->filled('slug')) {
            $this->merge(['slug' => str($this->input('name'))->slug()->toString()]);
        }

        if (! $this->has('is_leadership')) {
            $this->merge(['is_leadership' => false]);
        }

        if (! $this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'hierarchy_rank' => ['required', 'integer', 'min:1'],
            'display_order' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', new Enum(PositionCategory::class)],
            'scope' => ['required', new Enum(PositionScope::class)],
            'is_leadership' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'committee_type_ids' => ['nullable', 'array'],
            'committee_type_ids.*' => ['integer', 'distinct', 'exists:committee_types,id'],
        ];
    }
}
