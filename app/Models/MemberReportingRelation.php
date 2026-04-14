<?php

namespace App\Models;

use App\Enum\ReportingRelationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MemberReportingRelation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'relation_no',
        'subordinate_assignment_id',
        'superior_assignment_id',
        'committee_id',
        'relation_type',
        'is_primary',
        'is_active',
        'start_date',
        'end_date',
        'note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'relation_type' => ReportingRelationType::class,
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (MemberReportingRelation $relation) {
            if (empty($relation->uuid)) {
                $relation->uuid = (string) Str::uuid();
            }
        });
    }

    public function subordinateAssignment(): BelongsTo
    {
        return $this->belongsTo(CommitteeMemberAssignment::class, 'subordinate_assignment_id');
    }

    public function superiorAssignment(): BelongsTo
    {
        return $this->belongsTo(CommitteeMemberAssignment::class, 'superior_assignment_id');
    }

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class, 'committee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(MemberReportingRelationHistory::class, 'member_reporting_relation_id')->latest('created_at');
    }

    // Future placeholder: escalationChains()
    // Future placeholder: approvalRoutes()
    // Future placeholder: organogramNodes()
}
