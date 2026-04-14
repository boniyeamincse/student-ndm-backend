<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'position_id',
        'old_active_state',
        'new_active_state',
        'changed_by',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_active_state' => 'boolean',
            'new_active_state' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
