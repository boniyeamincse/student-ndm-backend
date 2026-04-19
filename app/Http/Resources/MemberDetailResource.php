<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'uuid'                    => $this->uuid,
            'member_no'               => $this->member_no,
            'full_name'               => $this->full_name,
            'email'                   => $this->email,
            'mobile'                  => $this->mobile,
            'photo_url'               => $this->photo ? asset('storage/' . $this->photo) : null,
            'gender'                  => $this->gender,
            'date_of_birth'           => $this->date_of_birth?->toDateString(),
            'blood_group'             => $this->blood_group,
            'bio'                     => $this->bio,
            'father_name'             => $this->father_name,
            'mother_name'             => $this->mother_name,
            'educational_institution' => $this->educational_institution,
            'department'              => $this->department,
            'academic_year'           => $this->academic_year,
            'occupation'              => $this->occupation,
            'address' => [
                'address_line' => $this->address_line,
                'village_area' => $this->village_area,
                'post_office'  => $this->post_office,
                'union_id'   => $this->union_id,
                'upazila'      => $this->upazila_id,
                'district'     => $this->district_id,
                'division'     => $this->division_id,
            ],
            'emergency_contact' => [
                'name'  => $this->emergency_contact_name,
                'phone' => $this->emergency_contact_phone,
            ],
            'status'                  => $this->status?->value,
            'status_label'            => $this->status?->label(),
            'status_note'             => $this->status_note,
            'last_status_changed_at'  => $this->last_status_changed_at?->toDateTimeString(),
            'joined_at'               => $this->joined_at?->toDateTimeString(),
            'approved_at'             => $this->approved_at?->toDateTimeString(),
            'user_id'                 => $this->user_id,
            'membership_application_id' => $this->membership_application_id,
            'created_at'              => $this->created_at?->toDateTimeString(),
            'updated_at'              => $this->updated_at?->toDateTimeString(),

            // Computed primary engagement
            'primary_committee_name'  => $this->committeeAssignments->first()?->committee?->name,
            'primary_position_name'   => $this->committeeAssignments->first()?->position?->name,
            'is_leadership'           => $this->committeeAssignments->count() > 0,
            'assignments_count'       => $this->committeeAssignments->count(),
            'leadership_summary'      => $this->committeeAssignments->where('is_leadership', true)->first()?->position?->name,
            'assignments_summary'     => $this->committeeAssignments->map(fn($a) => ($a->position?->name ?? 'Member') . ' (' . ($a->committee?->name ?? 'Unknown') . ')')->implode(', '),

            // Conditionally loaded relations
            'status_histories' => MemberStatusHistoryResource::collection($this->whenLoaded('statusHistories')),
            'user'             => $this->whenLoaded('user', fn () => [
                'id'     => $this->user?->id,
                'name'   => $this->user?->name,
                'email'  => $this->user?->email,
                'status' => $this->user?->status?->value,
            ]),
            'application'      => $this->whenLoaded('application', fn () => [
                'id'             => $this->application?->id,
                'application_no' => $this->application?->application_no,
                'status'         => $this->application?->status?->value,
            ]),
        ];
    }
}
