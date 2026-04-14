<?php

namespace App\Services;

use App\Enum\ProfileVisibility;
use App\Models\AccountSetting;
use App\Models\User;

class AccountSettingService
{
    public function getOrCreate(User $user): AccountSetting
    {
        return AccountSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'language' => 'en',
                'timezone' => 'Asia/Dhaka',
                'notification_email_enabled' => true,
                'notification_sms_enabled' => false,
                'notification_push_enabled' => false,
                'profile_visibility' => ProfileVisibility::MembersOnly,
                'show_email' => false,
                'show_phone' => false,
                'show_address' => false,
            ]
        );
    }

    public function update(User $user, array $data): AccountSetting
    {
        $settings = $this->getOrCreate($user);
        $settings->update($data);

        return $settings->fresh();
    }
}
