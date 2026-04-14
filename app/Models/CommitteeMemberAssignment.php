<?php

namespace App\Models;

use App\Enum\AssignmentStatus;
use App\Enum\AssignmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CommitteeMemberAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'assignment_no',
        'member_id',
        'committee_id',
        'position_id',
        'assignment_type',
        'is_primary',
        'is_leadership',
        'is_active',
        'appointed_by',
        'approved_by',
        'assigned_at',
        'approved_at',
        'start_date',
        'end_date',
        'status',
        'note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'assignment_type' => AssignmentType::class,
            'status' => AssignmentStatus::class,
            'is_primary' => 'boolean',
            'is_leadership' => 'boolean',
            'is_active' => 'boolean',
            'assigned_at' => 'datetime',
            'approved_at' => 'datetime',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CommitteeMemberAssignment $assignment) {
            if (empty($assignment->uuid)) {
                $assignment->uuid = (string) Str::uuid();
            }
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class, 'committee_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function appointedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appointed_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(CommitteeMemberAssignmentHistory::class, 'committee_member_assignment_id')->latest('created_at');
    }

    public function positionHistories(): HasMany
    {
        return $this->hasMany(CommitteeMemberPositionHistory::class, 'committee_member_assignment_id')->latest('created_at');
    }

    public function reportingToRelations(): HasMany
    {
        return $this->hasMany(MemberReportingRelation::class, 'subordinate_assignment_id');
    }

    public function reportedByRelations(): HasMany
    {
        return $this->hasMany(MemberReportingRelation::class, 'superior_assignment_id');
    }

    // Future placeholder: reportingManager()
    // Future placeholder: subordinateAssignments()
    // Future placeholder: publicOfficeBearerCards()
}
