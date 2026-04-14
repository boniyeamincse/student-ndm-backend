<?php

namespace App\Http\Requests;

use App\Enum\AssignmentStatus;
use App\Enum\AssignmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCommitteeMemberAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (! $this->has('assignment_type')) {
            $this->merge(['assignment_type' => AssignmentType::GeneralMember->value]);
        }
        if (! $this->has('is_primary')) {
            $this->merge(['is_primary' => false]);
        }
        if (! $this->has('is_leadership')) {
            $this->merge(['is_leadership' => false]);
        }
        if (! $this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
        if (! $this->has('status')) {
            $this->merge(['status' => AssignmentStatus::Active->value]);
        }
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'committee_id' => ['required', 'integer', 'exists:committees,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'assignment_type' => ['required', new Enum(AssignmentType::class)],
            'is_primary' => ['nullable', 'boolean'],
            'is_leadership' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'appointed_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_at' => ['nullable', 'date'],
            'approved_at' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', new Enum(AssignmentStatus::class)],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
