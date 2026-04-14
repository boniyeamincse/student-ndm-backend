<?php

namespace App\Models;

use App\Enum\ProfileUpdateRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileUpdateRequestHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'profile_update_request_id',
        'old_status',
        'new_status',
        'changed_by',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_status' => ProfileUpdateRequestStatus::class,
            'new_status' => ProfileUpdateRequestStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function profileUpdateRequest(): BelongsTo
    {
        return $this->belongsTo(ProfileUpdateRequest::class, 'profile_update_request_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
