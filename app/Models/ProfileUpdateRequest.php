<?php

namespace App\Models;

use App\Enum\ProfileUpdateRequestStatus;
use App\Enum\ProfileUpdateRequestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProfileUpdateRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'request_no',
        'user_id',
        'member_id',
        'request_type',
        'requested_changes',
        'status',
        'submitted_note',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'request_type' => ProfileUpdateRequestType::class,
            'status' => ProfileUpdateRequestStatus::class,
            'requested_changes' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ProfileUpdateRequest $request) {
            if (empty($request->uuid)) {
                $request->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProfileUpdateRequestHistory::class, 'profile_update_request_id')->orderByDesc('created_at');
    }
}
