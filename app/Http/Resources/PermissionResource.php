<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'guard_name'  => $this->guard_name,
            'module'      => $this->getModule(),
            'label'       => $this->getLabel(),
            'description' => $this->description ?? null,
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }

    /**
     * Extract module from permission name (e.g., "user" from "user.view").
     */
    private function getModule(): string
    {
        return explode('.', $this->name)[0] ?? 'system';
    }

    /**
     * Generate human-readable label from permission name.
     */
    private function getLabel(): string
    {
        return str_replace(['.', '_'], ' ', $this->name);
    }
}
