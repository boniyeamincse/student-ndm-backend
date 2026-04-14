<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyCommitteeAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'assignment_id' => $this->id,
            'assignment_no' => $this->assignment_no,
            'assignment_type' => $this->assignment_type?->value,
            'is_primary' => (bool) $this->is_primary,
            'is_leadership' => (bool) $this->is_leadership,
            'is_active' => (bool) $this->is_active,
            'status' => $this->status?->value,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'committee' => [
                'id' => $this->committee?->id,
                'committee_no' => $this->committee?->committee_no,
                'name' => $this->committee?->name,
                'slug' => $this->committee?->slug,
                'committee_type' => [
                    'id' => $this->committee?->committeeType?->id,
                    'name' => $this->committee?->committeeType?->name,
                ],
            ],
            'position' => $this->position ? [
                'id' => $this->position->id,
                'name' => $this->position->name,
                'hierarchy_rank' => $this->position->hierarchy_rank,
            ] : null,
        ];
    }
}
