<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'display_name'      => $this->name,
            'description'       => $this->description ?? null,
            'is_system_role'    => in_array($this->name, ['superadmin', 'admin', 'member']),
            'permissions'       => PermissionResource::collection($this->permissions),
            'permissions_count' => $this->permissions_count ?? $this->permissions->count(),
            'users_count'       => $this->users_count ?? 0,
            'users'             => UserBasicResource::collection($this->whenLoaded('users')),
            'created_at'        => $this->created_at?->toDateTimeString(),
            'updated_at'        => $this->updated_at?->toDateTimeString(),
        ];
    }
}
