<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this['user'];
        $member = $this['member'];
        $settings = $this['settings'];

        return [
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->status?->value,
                'roles' => $user->getRoleNames(),
                'profile_photo_url' => $member?->photo ? asset('storage/'.$member->photo) : ($user->profile_photo ? asset('storage/'.$user->profile_photo) : null),
                'created_at' => $user->created_at?->toDateTimeString(),
            ],
            'member' => $member ? [
                'member_id' => $member->id,
                'member_no' => $member->member_no ?? $member->application_no ?? null,
                'full_name' => $member->full_name,
                'gender' => $member->gender,
                'date_of_birth' => $member->date_of_birth?->toDateString(),
                'blood_group' => $member->blood_group,
                'educational_institution' => $member->educational_institution,
                'department' => $member->department,
                'academic_year' => $member->academic_year,
                'occupation' => $member->occupation,
                'address_line' => $member->address_line,
                'village_area' => $member->village_area,
                'post_office' => $member->post_office,
                'division_id' => $member->division_id,
                'division_name_en' => $member->division?->name_en,
                'division_name_bn' => $member->division?->name_bn,
                'district_id' => $member->district_id,
                'district_name_en' => $member->district?->name_en,
                'district_name_bn' => $member->district?->name_bn,
                'upazila_id' => $member->upazila_id,
                'upazila_name_en' => $member->upazila?->name_en,
                'upazila_name_bn' => $member->upazila?->name_bn,
                'union_id' => $member->union_id,
                'union_name_en' => $member->union?->name_en,
                'union_name_bn' => $member->union?->name_bn,
                'emergency_contact_name' => $member->emergency_contact_name,
                'emergency_contact_phone' => $member->emergency_contact_phone,
                'bio' => $member->bio ?? null,
                'status' => $member->status instanceof \UnitEnum ? $member->status->value : $member->status,
                'joined_at' => ($member->joined_at ?? $member->created_at)->toDateString(),
            ] : null,
            'account_settings' => new MyAccountSettingsResource($settings),
            'quick_summary' => [
                'active_assignments_count' => (int) ($this['active_assignments_count'] ?? 0),
                'leadership_assignments_count' => (int) ($this['leadership_assignments_count'] ?? 0),
                'subordinates_count' => (int) ($this['subordinates_count'] ?? 0),
                'pending_profile_requests_count' => (int) ($this['pending_profile_requests_count'] ?? 0),
            ],
        ];
    }
}
