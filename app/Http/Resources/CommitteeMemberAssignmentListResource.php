<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommitteeMemberAssignmentListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'assignment_no' => $this->assignment_no,
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member?->id,
                'member_no' => $this->member?->member_no,
                'full_name' => $this->member?->full_name,
                'email' => $this->member?->email,
                'mobile' => $this->member?->mobile,
            ]),
            'committee' => $this->whenLoaded('committee', fn () => [
                'id' => $this->committee?->id,
                'committee_no' => $this->committee?->committee_no,
                'name' => $this->committee?->name,
            ]),
            'position' => $this->whenLoaded('position', fn () => [
                'id' => $this->position?->id,
                'name' => $this->position?->name,
                'hierarchy_rank' => $this->position?->hierarchy_rank,
            ]),
            'assignment_type' => $this->assignment_type?->value,
            'is_primary' => $this->is_primary,
            'is_leadership' => $this->is_leadership,
            'is_active' => $this->is_active,
            'status' => $this->status?->value,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'assigned_at' => $this->assigned_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
