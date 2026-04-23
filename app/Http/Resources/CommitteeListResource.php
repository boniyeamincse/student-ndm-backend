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
            'committee_type_id' => $this->committee_type_id,
            'committee_type_name' => $this->committeeType?->name,
            'committee_type' => $this->whenLoaded('committeeType', fn () => [
                'id' => $this->committeeType?->id,
                'name' => $this->committeeType?->name,
                'slug' => $this->committeeType?->slug,
                'hierarchy_order' => $this->committeeType?->hierarchy_order,
            ]),
            'parent_id' => $this->parent_id,
            'parent_name' => $this->parentCommittee?->name,
            'parent' => $this->whenLoaded('parentCommittee', fn () => [
                'id' => $this->parentCommittee?->id,
                'name' => $this->parentCommittee?->name,
                'committee_no' => $this->parentCommittee?->committee_no,
            ]),
            'division_id' => $this->division_id,
            'district_id' => $this->district_id,
            'upazila_id' => $this->upazila_id,
            'union_id' => $this->union_id,
            'division_name' => $this->division?->name_en,
            'district_name' => $this->district?->name_en,
            'upazila_name' => $this->upazila?->name_en,
            'union_name' => $this->union?->name_en,
            'child_committees_count' => (int) ($this->child_committees_count ?? 0),
            'status' => $this->status?->value,
            'is_current' => $this->is_current,
            'start_date' => $this->start_date?->toDateString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
