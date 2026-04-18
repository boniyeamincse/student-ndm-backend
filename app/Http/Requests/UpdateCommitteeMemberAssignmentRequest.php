<?php

namespace App\Http\Requests;

use App\Enum\AssignmentStatus;
use App\Enum\AssignmentType;
use App\Http\Requests\Concerns\NormalizesEmptyValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCommitteeMemberAssignmentRequest extends FormRequest
{
    use NormalizesEmptyValues;

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->normalizeEmptyToNull([
            'position_id',
            'appointed_by',
            'approved_by',
            'assigned_at',
            'approved_at',
            'start_date',
            'end_date',
            'note',
        ]);
    }

    public function rules(): array
    {
        return [
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
