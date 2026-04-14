<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileUpdateRequestListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'request_no' => $this->request_no,
            'request_type' => $this->request_type?->value,
            'status' => $this->status?->value,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member?->id,
                'member_no' => $this->member?->member_no,
                'full_name' => $this->member?->full_name,
            ]),
            'submitted_note' => $this->submitted_note,
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
