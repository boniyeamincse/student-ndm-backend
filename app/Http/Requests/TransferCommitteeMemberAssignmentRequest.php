<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferCommitteeMemberAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (! $this->has('keep_old_assignment_active')) {
            $this->merge(['keep_old_assignment_active' => false]);
        }
    }

    public function rules(): array
    {
        return [
            'target_committee_id' => ['required', 'integer', 'exists:committees,id'],
            'target_position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'transfer_mode' => ['required', 'string', 'in:move,copy'],
            'effective_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'keep_old_assignment_active' => ['nullable', 'boolean'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }
}
