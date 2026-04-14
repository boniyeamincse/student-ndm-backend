<?php

namespace App\Models;

use App\Enum\PostContentType;
use App\Enum\PostStatus;
use App\Enum\PostVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'post_no',
        'title',
        'slug',
        'excerpt',
        'content',
        'content_type',
        'post_category_id',
        'committee_id',
        'author_id',
        'editor_id',
        'featured_image',
        'featured_image_alt',
        'tags',
        'status',
        'visibility',
        'is_featured',
        'allow_on_homepage',
        'published_at',
        'scheduled_at',
        'last_edited_at',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'view_count',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'featured_image_url',
    ];

    protected function casts(): array
    {
        return [
            'content_type' => PostContentType::class,
            'status' => PostStatus::class,
            'visibility' => PostVisibility::class,
            'tags' => 'array',
            'is_featured' => 'boolean',
            'allow_on_homepage' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'last_edited_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Post $post) {
            if (empty($post->uuid)) {
                $post->uuid = (string) Str::uuid();
            }
        });
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image ? asset('storage/'.$this->featured_image) : null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class, 'committee_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(PostStatusHistory::class, 'post_id')->latest('created_at');
    }

    // Future placeholder: attachments()
    // Future placeholder: relatedPosts()
    // Future placeholder: seoObject()
    // Future placeholder: translations()
}
