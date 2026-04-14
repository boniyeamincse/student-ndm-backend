<?php

namespace App\Models;

use App\Enum\NoticeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoticeStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'notice_id',
        'old_status',
        'new_status',
        'changed_by',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_status' => NoticeStatus::class,
            'new_status' => NoticeStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class, 'notice_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
