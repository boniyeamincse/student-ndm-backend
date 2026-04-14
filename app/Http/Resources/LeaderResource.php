<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'relation_id' => $this->id,
            'relation_no' => $this->relation_no,
            'relation_type' => $this->relation_type?->value,
            'is_primary' => $this->is_primary,
            'is_active' => $this->is_active,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'subordinate_assignment' => [
                'id' => $this->subordinateAssignment?->id,
                'assignment_no' => $this->subordinateAssignment?->assignment_no,
                'member' => [
                    'id' => $this->subordinateAssignment?->member?->id,
                    'full_name' => $this->subordinateAssignment?->member?->full_name,
                    'member_no' => $this->subordinateAssignment?->member?->member_no,
                ],
            ],
            'superior_assignment' => [
                'id' => $this->superiorAssignment?->id,
                'assignment_no' => $this->superiorAssignment?->assignment_no,
                'member' => [
                    'id' => $this->superiorAssignment?->member?->id,
                    'full_name' => $this->superiorAssignment?->member?->full_name,
                    'member_no' => $this->superiorAssignment?->member?->member_no,
                ],
                'position' => [
                    'id' => $this->superiorAssignment?->position?->id,
                    'name' => $this->superiorAssignment?->position?->name,
                    'hierarchy_rank' => $this->superiorAssignment?->position?->hierarchy_rank,
                    'is_leadership' => $this->superiorAssignment?->position?->is_leadership,
                ],
            ],
            'committee' => [
                'id' => $this->committee?->id,
                'name' => $this->committee?->name,
            ],
        ];
    }
}
