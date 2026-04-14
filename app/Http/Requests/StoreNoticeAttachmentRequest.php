<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoticeAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attachments' => ['required', 'array', 'min:1'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,csv,zip', 'max:8192'],
        ];
    }
}
