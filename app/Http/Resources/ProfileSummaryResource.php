<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this['name'] ?? null,
            'photo_url' => $this['photo_url'] ?? null,
            'member_no' => $this['member_no'] ?? null,
            'primary_role' => $this['primary_role'] ?? null,
            'main_committee' => $this['main_committee'] ?? null,
            'primary_position' => $this['primary_position'] ?? null,
            'leader_name' => $this['leader_name'] ?? null,
            'subordinate_count' => (int) ($this['subordinate_count'] ?? 0),
            'unread_notices_count' => 0,
            'pending_profile_requests_count' => (int) ($this['pending_profile_requests_count'] ?? 0),
        ];
    }
}
