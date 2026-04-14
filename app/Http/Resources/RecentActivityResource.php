<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Normalised activity item from composed history sources.
 *
 * Expected $this->resource keys:
 *   type, module, title, description, actor, related_entity_type,
 *   related_entity_id, created_at
 */
class RecentActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type'                => $this->resource['type']                ?? null,
            'module'              => $this->resource['module']              ?? null,
            'title'               => $this->resource['title']               ?? null,
            'description'         => $this->resource['description']         ?? null,
            'actor'               => $this->resource['actor']               ?? null,
            'related_entity_type' => $this->resource['related_entity_type'] ?? null,
            'related_entity_id'   => $this->resource['related_entity_id']   ?? null,
            'created_at'          => $this->resource['created_at']          ?? null,
        ];
    }
}
