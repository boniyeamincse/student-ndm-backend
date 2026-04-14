<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoticeAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'file_name' => $this->file_name,
            'original_name' => $this->original_name,
            'file_extension' => $this->file_extension,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'file_type' => $this->file_type?->value,
            'sort_order' => $this->sort_order,
            'file_url' => $this->file_url,
            'uploaded_by' => $this->whenLoaded('uploader', fn () => [
                'id' => $this->uploader?->id,
                'name' => $this->uploader?->name,
                'email' => $this->uploader?->email,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
