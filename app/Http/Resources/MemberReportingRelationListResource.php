<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberReportingRelationListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'relation_no' => $this->relation_no,
            'subordinate_assignment' => [
                'id' => $this->subordinateAssignment?->id,
                'assignment_no' => $this->subordinateAssignment?->assignment_no,
                'member' => [
                    'id' => $this->subordinateAssignment?->member?->id,
                    'member_no' => $this->subordinateAssignment?->member?->member_no,
                    'full_name' => $this->subordinateAssignment?->member?->full_name,
                ],
                'position' => [
                    'id' => $this->subordinateAssignment?->position?->id,
                    'name' => $this->subordinateAssignment?->position?->name,
                    'hierarchy_rank' => $this->subordinateAssignment?->position?->hierarchy_rank,
                ],
            ],
            'superior_assignment' => [
                'id' => $this->superiorAssignment?->id,
                'assignment_no' => $this->superiorAssignment?->assignment_no,
                'member' => [
                    'id' => $this->superiorAssignment?->member?->id,
                    'member_no' => $this->superiorAssignment?->member?->member_no,
                    'full_name' => $this->superiorAssignment?->member?->full_name,
                ],
                'position' => [
                    'id' => $this->superiorAssignment?->position?->id,
                    'name' => $this->superiorAssignment?->position?->name,
                    'hierarchy_rank' => $this->superiorAssignment?->position?->hierarchy_rank,
                ],
            ],
            'committee' => [
                'id' => $this->committee?->id,
                'committee_no' => $this->committee?->committee_no,
                'name' => $this->committee?->name,
                'committee_type' => [
                    'id' => $this->committee?->committeeType?->id,
                    'name' => $this->committee?->committeeType?->name,
                ],
            ],
            'relation_type' => $this->relation_type?->value,
            'is_primary' => $this->is_primary,
            'is_active' => $this->is_active,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
