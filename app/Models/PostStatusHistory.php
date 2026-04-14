<?php

namespace App\Models;

use App\Enum\PostStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'old_status',
        'new_status',
        'changed_by',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_status' => PostStatus::class,
            'new_status' => PostStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
