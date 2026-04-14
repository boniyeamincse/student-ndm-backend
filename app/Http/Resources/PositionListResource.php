<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'code' => $this->code,
            'short_name' => $this->short_name,
            'hierarchy_rank' => $this->hierarchy_rank,
            'display_order' => $this->display_order,
            'category' => $this->category?->value,
            'scope' => $this->scope?->value,
            'is_leadership' => $this->is_leadership,
            'is_active' => $this->is_active,
            'mapped_committee_types_count' => $this->whenCounted('committeeTypes'),
            'mapped_committee_types' => $this->whenLoaded('committeeTypes', fn () => $this->committeeTypes->map(fn ($type) => [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
            ])->values()),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
