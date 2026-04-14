<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MyLeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignment_id' => ['nullable', 'integer', 'exists:committee_member_assignments,id'],
        ];
    }
}
