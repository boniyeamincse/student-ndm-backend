<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'uuid'         => $this->uuid,
            'name'         => $this->name,
            'username'     => $this->username,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'status'       => $this->status?->value,
            'roles'        => $this->getRoleNames(),
            'permissions'  => $this->getAllPermissions()->pluck('name'),
            'created_at'   => $this->created_at?->toIso8601String(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
