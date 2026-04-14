<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MemberNoticeListResource extends PublicNoticeListResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'visibility' => $this->visibility?->value,
            'audience_type' => $this->audience_type?->value,
            'requires_acknowledgement' => $this->requires_acknowledgement,
            'unread' => null, // Future placeholder: acknowledgment/read tracking
        ];
    }
}
