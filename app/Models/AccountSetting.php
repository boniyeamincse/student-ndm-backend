<?php

namespace App\Models;

use App\Enum\ProfileVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'language',
        'timezone',
        'notification_email_enabled',
        'notification_sms_enabled',
        'notification_push_enabled',
        'profile_visibility',
        'show_email',
        'show_phone',
        'show_address',
    ];

    protected function casts(): array
    {
        return [
            'notification_email_enabled' => 'boolean',
            'notification_sms_enabled' => 'boolean',
            'notification_push_enabled' => 'boolean',
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
            'show_address' => 'boolean',
            'profile_visibility' => ProfileVisibility::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Future placeholder: socialLinkVisibility()
    // Future placeholder: idCardPreferences()
}
