<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommitteeTypeRequest extends FormRequest
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
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('committee_types', 'name')->withoutTrashed()],
            'slug' => ['required', 'string', 'max:120', Rule::unique('committee_types', 'slug')->withoutTrashed()],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('committee_types', 'code')->withoutTrashed()],
            'hierarchy_order' => ['required', 'integer', 'min:1', Rule::unique('committee_types', 'hierarchy_order')->withoutTrashed()],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
