<?php

namespace App\Http\Requests;

use App\Enum\CommitteeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCommitteeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $nullableFields = [
            'parent_id',
            'code',
            'division_id',
            'district_id',
            'upazila_id',
            'union_id',
            'address_line',
            'office_phone',
            'office_email',
            'description',
            'start_date',
            'end_date',
            'formed_by',
            'approved_by',
            'formed_at',
            'approved_at',
            'notes',
        ];

        foreach ($nullableFields as $field) {
            if ($this->has($field) && trim((string) $this->input($field)) === '') {
                $this->merge([$field => null]);
            }
        }

        if ($this->filled('name') && ! $this->filled('slug')) {
            $this->merge(['slug' => str($this->input('name'))->slug()->toString()]);
        }

        if (! $this->has('status')) {
            $this->merge(['status' => CommitteeStatus::Active->value]);
        }

        if (! $this->has('is_current')) {
            $this->merge(['is_current' => true]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'committee_type_id' => ['required', 'integer', 'exists:committee_types,id'],
            'parent_id' => ['nullable', 'integer', 'exists:committees,id'],
            'code' => ['nullable', 'string', 'max:30'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'upazila_id' => ['nullable', 'integer', 'exists:upazilas,id'],
            'union_id' => ['nullable', 'integer', 'exists:unions,id'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'office_phone' => ['nullable', 'string', 'max:30'],
            'office_email' => ['nullable', 'email', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', new Enum(CommitteeStatus::class)],
            'is_current' => ['nullable', 'boolean'],
            'formed_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'formed_at' => ['nullable', 'date'],
            'approved_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
