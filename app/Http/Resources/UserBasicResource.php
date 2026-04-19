<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBasicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'uuid'      => $this->uuid,
            'name'      => $this->name,
            'email'     => $this->email,
            'photo_url' => $this->profile_photo ? asset('storage/' . $this->profile_photo) : null,
            'status'    => $this->status?->value,
        ];
    }
}
