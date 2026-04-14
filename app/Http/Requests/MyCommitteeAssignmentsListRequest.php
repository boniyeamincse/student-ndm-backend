<?php

namespace App\Http\Requests;

use App\Enum\AssignmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MyCommitteeAssignmentsListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', new Enum(AssignmentStatus::class)],
            'active_only' => ['nullable', 'boolean'],
            'leadership_only' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'in:latest,start_date,hierarchy'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
