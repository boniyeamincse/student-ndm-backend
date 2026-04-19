<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'display_name'      => $this->name,
            'description'       => $this->description ?? null,
            'permissions_count' => $this->permissions_count ?? $this->permissions()->count(),
            'users_count'       => $this->users()->count(),
            'is_system_role'    => in_array($this->name, ['superadmin', 'admin', 'member']),
            'created_at'        => $this->created_at?->toDateTimeString(),
        ];
    }
}
