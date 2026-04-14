<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionDetailResource extends JsonResource
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
            'description' => $this->description,
            'category' => $this->category?->value,
            'scope' => $this->scope?->value,
            'is_leadership' => $this->is_leadership,
            'is_active' => $this->is_active,
            'mapped_committee_types' => $this->whenLoaded('committeeTypes', fn () => $this->committeeTypes->map(fn ($type) => [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
                'hierarchy_order' => $type->hierarchy_order,
                'is_active' => $type->is_active,
            ])->values()),
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
                'email' => $this->creator?->email,
            ]),
            'updated_by' => $this->whenLoaded('updater', fn () => [
                'id' => $this->updater?->id,
                'name' => $this->updater?->name,
                'email' => $this->updater?->email,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'status_history_timeline' => PositionStatusHistoryResource::collection($this->whenLoaded('statusHistories')),

            // Future placeholder: assigned_committee_members
            // Future placeholder: office_bearers
            // Future placeholder: reporting_relationships
        ];
    }
}
