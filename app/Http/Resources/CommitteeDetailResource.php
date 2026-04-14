<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommitteeDetailResource extends JsonResource
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
                'code' => $this->committeeType?->code,
                'hierarchy_order' => $this->committeeType?->hierarchy_order,
            ]),
            'parent' => $this->whenLoaded('parentCommittee', fn () => [
                'id' => $this->parentCommittee?->id,
                'name' => $this->parentCommittee?->name,
                'committee_no' => $this->parentCommittee?->committee_no,
                'slug' => $this->parentCommittee?->slug,
            ]),
            'child_committees' => $this->whenLoaded('childCommittees', fn () => $this->childCommittees->map(fn ($child) => [
                'id' => $child->id,
                'name' => $child->name,
                'committee_no' => $child->committee_no,
                'status' => $child->status?->value,
                'is_current' => $child->is_current,
            ])->values()),
            'code' => $this->code,
            'division_name' => $this->division_name,
            'district_name' => $this->district_name,
            'upazila_name' => $this->upazila_name,
            'union_name' => $this->union_name,
            'address_line' => $this->address_line,
            'office_phone' => $this->office_phone,
            'office_email' => $this->office_email,
            'description' => $this->description,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status?->value,
            'is_current' => $this->is_current,
            'formed_by' => $this->whenLoaded('formedByUser', fn () => [
                'id' => $this->formedByUser?->id,
                'name' => $this->formedByUser?->name,
                'email' => $this->formedByUser?->email,
            ]),
            'approved_by' => $this->whenLoaded('approvedByUser', fn () => [
                'id' => $this->approvedByUser?->id,
                'name' => $this->approvedByUser?->name,
                'email' => $this->approvedByUser?->email,
            ]),
            'formed_at' => $this->formed_at?->toDateTimeString(),
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'status_history_timeline' => CommitteeStatusHistoryResource::collection($this->whenLoaded('statusHistories')),

            // Future placeholder: committee_members
            // Future placeholder: committee_leaders
            // Future placeholder: committee_notices
        ];
    }
}
