<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecentActivitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module' => ['nullable', 'string', 'in:applications,members,committees,assignments,hierarchy,posts,notices,profile_requests'],
            'limit'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
