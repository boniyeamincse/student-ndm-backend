<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'uuid'      => $this->uuid,
            'member_no' => $this->member_no,
            'full_name' => $this->full_name,
            'email'     => $this->email,
            'mobile'    => $this->mobile,
            'photo_url' => $this->photo ? asset('storage/'.$this->photo) : null,
            'gender'    => $this->gender,
            'division_name' => $this->division_name,
            'district_name' => $this->district_name,
            'status'        => $this->status?->value,
            'status_label'  => $this->status?->label(),
            'joined_at'     => $this->joined_at?->toIso8601String(),
            'approved_at'   => $this->approved_at?->toIso8601String(),
        ];
    }
}
