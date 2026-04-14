<?php

namespace App\Models;

use App\Enum\NoticeAttachmentFileType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NoticeAttachment extends Model
{
    protected $fillable = [
        'uuid',
        'notice_id',
        'file_name',
        'original_name',
        'file_path',
        'file_extension',
        'mime_type',
        'file_size',
        'file_type',
        'sort_order',
        'uploaded_by',
    ];

    protected $appends = [
        'file_url',
    ];

    protected function casts(): array
    {
        return [
            'file_type' => NoticeAttachmentFileType::class,
            'file_size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (NoticeAttachment $attachment) {
            if (empty($attachment->uuid)) {
                $attachment->uuid = (string) Str::uuid();
            }
        });
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/'.$this->file_path) : null;
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class, 'notice_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
