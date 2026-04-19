<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'uuid'         => $this->uuid,
            'member_no'    => $this->member_no,
            'full_name'    => $this->full_name,
            'email'        => $this->email,
            'mobile'       => $this->mobile,
            'photo_url'    => $this->photo ? asset('storage/' . $this->photo) : null,
            'gender'       => $this->gender,
            'division'     => $this->division_id,
            'district'     => $this->district_id,
            'upazila'      => $this->upazila_id,
            'status'       => $this->status?->value,
            'status_label' => $this->status?->label(),
            'joined_at'    => $this->joined_at?->toDateTimeString(),
            'user_id'      => $this->user_id,
        ];
    }
}
