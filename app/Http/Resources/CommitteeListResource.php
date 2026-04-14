<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommitteeListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'committee_no' => $this->committee_no,
            'name' => $this->name,
            'slug' => $this->slug,
            'committee_type' => $this->whenLoaded('committeeType', fn () => [
                'id' => $this->committeeType?->id,
                'name' => $this->committeeType?->name,
                'slug' => $this->committeeType?->slug,
                'hierarchy_order' => $this->committeeType?->hierarchy_order,
            ]),
            'parent' => $this->whenLoaded('parentCommittee', fn () => [
                'id' => $this->parentCommittee?->id,
                'name' => $this->parentCommittee?->name,
                'committee_no' => $this->parentCommittee?->committee_no,
            ]),
            'division_name' => $this->division_name,
            'district_name' => $this->district_name,
            'upazila_name' => $this->upazila_name,
            'union_name' => $this->union_name,
            'status' => $this->status?->value,
            'is_current' => $this->is_current,
            'start_date' => $this->start_date?->toDateString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
