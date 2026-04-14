<?php

namespace App\Enum;

enum ProfileUpdateRequestType: string
{
    case ProfileUpdate = 'profile_update';
    case EmailChange = 'email_change';
    case MobileChange = 'mobile_change';
    case SensitiveInfoChange = 'sensitive_info_change';
    case PhotoChange = 'photo_change';
    case Other = 'other';
}
