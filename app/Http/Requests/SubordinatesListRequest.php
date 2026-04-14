<?php

namespace App\Http\Requests;

use App\Enum\ReportingRelationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SubordinatesListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'relation_type' => ['nullable', new Enum(ReportingRelationType::class)],
            'is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'leadership_only' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'in:rank,member_name,created_at'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
