<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compact resource for list endpoints.
 * Returns summary fields only — reduces payload on paginated lists.
 */
class MembershipApplicationListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'application_no' => $this->application_no,
            'full_name'      => $this->full_name,
            'email'          => $this->email,
            'mobile'         => $this->mobile,
            'district_id'  => $this->district_id,
            'division_id'  => $this->division_id,
            'status'         => $this->status?->value,
            'status_label'   => $this->status?->label(),
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
