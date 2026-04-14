<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyAccountSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'language' => $this->language,
            'timezone' => $this->timezone,
            'notification_email_enabled' => (bool) $this->notification_email_enabled,
            'notification_sms_enabled' => (bool) $this->notification_sms_enabled,
            'notification_push_enabled' => (bool) $this->notification_push_enabled,
            'profile_visibility' => $this->profile_visibility?->value,
            'show_email' => (bool) $this->show_email,
            'show_phone' => (bool) $this->show_phone,
            'show_address' => (bool) $this->show_address,
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
