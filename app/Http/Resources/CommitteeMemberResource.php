<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommitteeMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'assignment_id' => $this->id,
            'assignment_no' => $this->assignment_no,
            'member' => [
                'id' => $this->member?->id,
                'member_no' => $this->member?->member_no,
                'full_name' => $this->member?->full_name,
                'email' => $this->member?->email,
                'mobile' => $this->member?->mobile,
            ],
            'position' => $this->position ? [
                'id' => $this->position->id,
                'name' => $this->position->name,
                'hierarchy_rank' => $this->position->hierarchy_rank,
                'display_order' => $this->position->display_order,
            ] : null,
            'assignment_type' => $this->assignment_type?->value,
            'is_primary' => $this->is_primary,
            'is_leadership' => $this->is_leadership,
            'status' => $this->status?->value,
            'is_active' => $this->is_active,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'assigned_at' => $this->assigned_at?->toDateTimeString(),
        ];
    }
}
