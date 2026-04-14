<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full application detail resource — used for GET single and review/approval responses.
 */
class MembershipApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'application_no' => $this->application_no,

            // Applicant info
            'full_name'      => $this->full_name,
            'father_name'    => $this->father_name,
            'mother_name'    => $this->mother_name,
            'gender'         => $this->gender,
            'date_of_birth'  => $this->date_of_birth?->toDateString(),
            'email'          => $this->email,
            'mobile'         => $this->mobile,
            'photo_url'      => $this->photo ? asset('storage/'.$this->photo) : null,
            'national_id'    => $this->national_id,
            'student_id'     => $this->student_id,
            'blood_group'    => $this->blood_group,
            'occupation'     => $this->occupation,

            // Academic
            'educational_institution' => $this->educational_institution,
            'department'              => $this->department,
            'academic_year'           => $this->academic_year,

            // Address
            'address_line'  => $this->address_line,
            'village_area'  => $this->village_area,
            'post_office'   => $this->post_office,
            'union_name'    => $this->union_name,
            'upazila_name'  => $this->upazila_name,
            'district_name' => $this->district_name,
            'division_name' => $this->division_name,

            // Emergency
            'emergency_contact_name'  => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,

            // Application meta
            'desired_committee_level' => $this->desired_committee_level,
            'motivation'              => $this->motivation,

            // Workflow status
            'status'       => $this->status?->value,
            'status_label' => $this->status?->label(),

            // Reviewer
            'reviewed_by' => $this->reviewer?->name,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),

            // Approver
            'approved_by' => $this->approver?->name,
            'approved_at' => $this->approved_at?->toIso8601String(),

            // Rejector
            'rejected_by'       => $this->rejector?->name,
            'rejected_at'       => $this->rejected_at?->toIso8601String(),
            'rejection_reason'  => $this->rejection_reason,

            'remarks'    => $this->remarks,
            'created_at' => $this->created_at?->toIso8601String(),

            // Status timeline
            'status_history' => ApplicationStatusHistoryResource::collection(
                $this->whenLoaded('statusHistories')
            ),

            // Linked member (if approved)
            'member' => new MemberResource($this->whenLoaded('member')),
        ];
    }
}
