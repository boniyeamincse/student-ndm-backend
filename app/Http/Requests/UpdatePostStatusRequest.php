<?php

namespace App\Http\Requests;

use App\Enum\PostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePostStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(PostStatus::class)],
            'note' => ['nullable', 'string', 'max:1500'],
        ];
    }
}
