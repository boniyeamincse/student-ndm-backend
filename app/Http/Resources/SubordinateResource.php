<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubordinateResource extends JsonResource
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
                    'member_no' => $this->subordinateAssignment?->member?->member_no,
                    'full_name' => $this->subordinateAssignment?->member?->full_name,
                    'photo_url' => $this->subordinateAssignment?->member?->photo ? asset('storage/'.$this->subordinateAssignment->member->photo) : null,
                ],
                'position' => [
                    'id' => $this->subordinateAssignment?->position?->id,
                    'name' => $this->subordinateAssignment?->position?->name,
                    'hierarchy_rank' => $this->subordinateAssignment?->position?->hierarchy_rank,
                    'is_leadership' => $this->subordinateAssignment?->position?->is_leadership,
                ],
            ],
        ];
    }
}
