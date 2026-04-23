<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $primaryAssignment = $this->committeeAssignments->first();
        $leadershipAssignment = $this->committeeAssignments->firstWhere('is_leadership', true);

        return [
            'id'           => $this->id,
            'uuid'         => $this->uuid,
            'member_no'    => $this->member_no,
            'full_name'    => $this->full_name,
            'email'        => $this->email,
            'mobile'       => $this->mobile,
            'photo'        => $this->photo ? asset('storage/' . $this->photo) : null,
            'photo_url'    => $this->photo ? asset('storage/' . $this->photo) : null,
            'gender'       => $this->gender,
            'division_id'  => $this->division_id,
            'district_id'  => $this->district_id,
            'upazila_id'   => $this->upazila_id,
            'union_id'     => $this->union_id,
            'division'     => $this->division_id,
            'district'     => $this->district_id,
            'upazila'      => $this->upazila_id,
            'division_name' => $this->division?->name_en,
            'district_name' => $this->district?->name_en,
            'upazila_name'  => $this->upazila?->name_en,
            'union_name'    => $this->union?->name_en,
            'status'       => $this->status?->value,
            'status_label' => $this->status?->label(),
            'joined_at'    => $this->joined_at?->toDateTimeString(),
            'user_id'      => $this->user_id,
            'linked_user_status' => $this->user?->status?->value,
            'is_leadership' => $this->committeeAssignments->contains('is_leadership', true),
            'leadership_summary' => $leadershipAssignment?->position?->name,
            'primary_committee_name' => $primaryAssignment?->committee?->name,
            'primary_position_name' => $primaryAssignment?->position?->name,
        ];
    }
}
