<?php

namespace App\Models;

use App\Enum\AssignmentHistoryAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitteeMemberAssignmentHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'committee_member_assignment_id',
        'action',
        'old_values',
        'new_values',
        'changed_by',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => AssignmentHistoryAction::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function committeeMemberAssignment(): BelongsTo
    {
        return $this->belongsTo(CommitteeMemberAssignment::class, 'committee_member_assignment_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
