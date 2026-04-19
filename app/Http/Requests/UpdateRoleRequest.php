<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role')->id ?? $this->route('role');

        return [
            'name'          => ['sometimes', 'required', 'string', 'max:150', 'regex:/^[a-z0-9_]+$/', Rule::unique('roles', 'name')->ignore($roleId)],
            'display_name'  => ['nullable', 'string', 'max:150'],
            'description'   => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Role name must contain only lowercase letters, numbers, and underscores.',
        ];
    }
}
