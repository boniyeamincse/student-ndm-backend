<?php

namespace App\Http\Requests;

use App\Enum\ReportingRelationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CommitteeHierarchyTreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('active_only')) {
            $this->merge(['active_only' => true]);
        }
        if (! $this->has('relation_type')) {
            $this->merge(['relation_type' => ReportingRelationType::DirectReport->value]);
        }
        if (! $this->has('include_non_primary')) {
            $this->merge(['include_non_primary' => false]);
        }
    }

    public function rules(): array
    {
        return [
            'active_only' => ['nullable', 'boolean'],
            'relation_type' => ['nullable', new Enum(ReportingRelationType::class)],
            'include_non_primary' => ['nullable', 'boolean'],
            'include_orphans' => ['nullable', 'boolean'],
        ];
    }
}
