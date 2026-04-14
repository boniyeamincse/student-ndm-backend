<?php

namespace App\Models;

use App\Enum\NoticeAudienceType;
use App\Enum\NoticePriority;
use App\Enum\NoticeStatus;
use App\Enum\NoticeType;
use App\Enum\NoticeVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Notice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'notice_no',
        'title',
        'slug',
        'summary',
        'content',
        'notice_type',
        'priority',
        'status',
        'visibility',
        'audience_type',
        'committee_id',
        'created_by',
        'updated_by',
        'author_id',
        'approver_id',
        'featured_image',
        'featured_image_alt',
        'publish_at',
        'expires_at',
        'is_pinned',
        'requires_acknowledgement',
        'attachment_count',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'last_edited_at',
    ];

    protected $appends = [
        'featured_image_url',
    ];

    protected function casts(): array
    {
        return [
            'notice_type' => NoticeType::class,
            'priority' => NoticePriority::class,
            'status' => NoticeStatus::class,
            'visibility' => NoticeVisibility::class,
            'audience_type' => NoticeAudienceType::class,
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_edited_at' => 'datetime',
            'is_pinned' => 'boolean',
            'requires_acknowledgement' => 'boolean',
            'attachment_count' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Notice $notice) {
            if (empty($notice->uuid)) {
                $notice->uuid = (string) Str::uuid();
            }
        });
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image ? asset('storage/'.$this->featured_image) : null;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class, 'committee_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(NoticeAttachment::class, 'notice_id')->orderBy('sort_order');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(NoticeStatusHistory::class, 'notice_id')->latest('created_at');
    }

    public function audienceRules(): HasMany
    {
        return $this->hasMany(NoticeAudienceRule::class, 'notice_id');
    }

    // Future placeholder: acknowledgements()
    // Future placeholder: notifications()
    // Future placeholder: recipients()
    // Future placeholder: readLogs()
}
