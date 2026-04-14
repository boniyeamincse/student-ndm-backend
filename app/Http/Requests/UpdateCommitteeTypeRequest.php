<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommitteeTypeRequest extends FormRequest
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
        $committeeTypeId = $this->route('committeeType');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('committee_types', 'name')->ignore($committeeTypeId)->withoutTrashed()],
            'slug' => ['required', 'string', 'max:120', Rule::unique('committee_types', 'slug')->ignore($committeeTypeId)->withoutTrashed()],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('committee_types', 'code')->ignore($committeeTypeId)->withoutTrashed()],
            'hierarchy_order' => ['required', 'integer', 'min:1', Rule::unique('committee_types', 'hierarchy_order')->ignore($committeeTypeId)->withoutTrashed()],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
