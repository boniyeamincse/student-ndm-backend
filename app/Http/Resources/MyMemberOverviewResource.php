<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyMemberOverviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'member_id' => $this->id,
            'member_no' => $this->member_no,
            'full_name' => $this->full_name,
            'status' => $this->status?->value,
            'joined_at' => $this->joined_at?->toDateString(),
            'photo_url' => $this->photo ? asset('storage/'.$this->photo) : null,
            'active_assignments_count' => (int) ($this->active_assignments_count ?? 0),
            'leadership_assignments_count' => (int) ($this->leadership_assignments_count ?? 0),
            'latest_assignment' => $this->whenLoaded('latestAssignment', function () {
                return [
                    'assignment_id' => $this->latestAssignment?->id,
                    'assignment_no' => $this->latestAssignment?->assignment_no,
                    'committee_name' => $this->latestAssignment?->committee?->name,
                    'position_name' => $this->latestAssignment?->position?->name,
                    'is_primary' => (bool) $this->latestAssignment?->is_primary,
                    'is_leadership' => (bool) $this->latestAssignment?->is_leadership,
                ];
            }),
            'leader' => $this->leader ?? null,
        ];
    }
}
