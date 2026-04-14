<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitteeMemberPositionHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'committee_member_assignment_id',
        'old_position_id',
        'new_position_id',
        'changed_by',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function committeeMemberAssignment(): BelongsTo
    {
        return $this->belongsTo(CommitteeMemberAssignment::class, 'committee_member_assignment_id');
    }

    public function oldPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'old_position_id');
    }

    public function newPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'new_position_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
