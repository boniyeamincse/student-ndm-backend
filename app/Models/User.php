<?php

namespace App\Models;

use App\Enum\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'uuid',
        'name',
        'username',
        'email',
        'phone',
        'profile_photo',
        'password',
        'role_type',
        'status',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'status'            => UserStatus::class,
        ];
    }

    // Auto-generate UUID on creation
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    public function canLogin(): bool
    {
        return $this->status?->canLogin() ?? false;
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class, 'user_id');
    }

    public function authoredPosts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    public function editedPosts(): HasMany
    {
        return $this->hasMany(Post::class, 'editor_id');
    }

    public function authoredNotices(): HasMany
    {
        return $this->hasMany(Notice::class, 'author_id');
    }

    public function editedNotices(): HasMany
    {
        return $this->hasMany(Notice::class, 'editor_id');
    }

    public function accountSettings(): HasOne
    {
        return $this->hasOne(AccountSetting::class, 'user_id');
    }

    public function profileUpdateRequests(): HasMany
    {
        return $this->hasMany(ProfileUpdateRequest::class, 'user_id');
    }

    public function reviewedProfileUpdateRequests(): HasMany
    {
        return $this->hasMany(ProfileUpdateRequest::class, 'reviewed_by');
    }
}

