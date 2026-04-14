<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberCommitteeAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'assignment_id' => $this->id,
            'assignment_no' => $this->assignment_no,
            'committee' => [
                'id' => $this->committee?->id,
                'committee_no' => $this->committee?->committee_no,
                'name' => $this->committee?->name,
                'committee_type' => [
                    'id' => $this->committee?->committeeType?->id,
                    'name' => $this->committee?->committeeType?->name,
                    'hierarchy_order' => $this->committee?->committeeType?->hierarchy_order,
                ],
            ],
            'position' => $this->position ? [
                'id' => $this->position->id,
                'name' => $this->position->name,
                'hierarchy_rank' => $this->position->hierarchy_rank,
            ] : null,
            'assignment_type' => $this->assignment_type?->value,
            'is_primary' => $this->is_primary,
            'is_leadership' => $this->is_leadership,
            'is_active' => $this->is_active,
            'status' => $this->status?->value,
            'assigned_at' => $this->assigned_at?->toDateTimeString(),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
