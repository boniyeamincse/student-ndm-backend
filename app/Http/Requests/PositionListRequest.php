<?php

namespace App\Http\Requests;

use App\Enum\PositionCategory;
use App\Enum\PositionScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PositionListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', new Enum(PositionCategory::class)],
            'scope' => ['nullable', new Enum(PositionScope::class)],
            'is_active' => ['nullable', 'boolean'],
            'is_leadership' => ['nullable', 'boolean'],
            'committee_type_id' => ['nullable', 'integer', 'exists:committee_types,id'],
            'sort_by' => ['nullable', 'string', 'in:hierarchy_rank,display_order,created_at,name'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
