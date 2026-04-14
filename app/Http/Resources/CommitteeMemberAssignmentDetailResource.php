<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommitteeMemberAssignmentDetailResource extends JsonResource
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
                'status' => $this->member?->status?->value,
            ]),
            'committee' => $this->whenLoaded('committee', fn () => [
                'id' => $this->committee?->id,
                'committee_no' => $this->committee?->committee_no,
                'name' => $this->committee?->name,
                'status' => $this->committee?->status?->value,
                'is_current' => $this->committee?->is_current,
                'committee_type' => [
                    'id' => $this->committee?->committeeType?->id,
                    'name' => $this->committee?->committeeType?->name,
                    'hierarchy_order' => $this->committee?->committeeType?->hierarchy_order,
                ],
            ]),
            'position' => $this->whenLoaded('position', fn () => [
                'id' => $this->position?->id,
                'name' => $this->position?->name,
                'hierarchy_rank' => $this->position?->hierarchy_rank,
                'is_leadership' => $this->position?->is_leadership,
                'is_active' => $this->position?->is_active,
            ]),
            'assignment_type' => $this->assignment_type?->value,
            'is_primary' => $this->is_primary,
            'is_leadership' => $this->is_leadership,
            'is_active' => $this->is_active,
            'status' => $this->status?->value,
            'appointed_by' => $this->whenLoaded('appointedByUser', fn () => [
                'id' => $this->appointedByUser?->id,
                'name' => $this->appointedByUser?->name,
                'email' => $this->appointedByUser?->email,
            ]),
            'approved_by' => $this->whenLoaded('approvedByUser', fn () => [
                'id' => $this->approvedByUser?->id,
                'name' => $this->approvedByUser?->name,
                'email' => $this->approvedByUser?->email,
            ]),
            'assigned_at' => $this->assigned_at?->toDateTimeString(),
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'note' => $this->note,
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
            'assignment_history_timeline' => CommitteeMemberAssignmentHistoryResource::collection($this->whenLoaded('histories')),
            'position_history_timeline' => CommitteeMemberPositionHistoryResource::collection($this->whenLoaded('positionHistories')),

            // Future placeholder: subordinate_assignments
            // Future placeholder: reporting_manager
            // Future placeholder: public_office_display_order
        ];
    }
}
