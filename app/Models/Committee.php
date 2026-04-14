<?php

namespace App\Models;

use App\Enum\CommitteeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Committee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'committee_no',
        'name',
        'slug',
        'committee_type_id',
        'parent_id',
        'code',
        'division_name',
        'district_name',
        'upazila_name',
        'union_name',
        'address_line',
        'office_phone',
        'office_email',
        'description',
        'start_date',
        'end_date',
        'status',
        'is_current',
        'formed_by',
        'approved_by',
        'formed_at',
        'approved_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommitteeStatus::class,
            'is_current' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'formed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Committee $committee) {
            if (empty($committee->uuid)) {
                $committee->uuid = (string) Str::uuid();
            }
        });
    }

    public function committeeType(): BelongsTo
    {
        return $this->belongsTo(CommitteeType::class, 'committee_type_id');
    }

    public function parentCommittee(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function childCommittees(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function formedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'formed_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(CommitteeStatusHistory::class, 'committee_id')->latest('created_at');
    }

    public function committeeAssignments(): HasMany
    {
        return $this->hasMany(CommitteeMemberAssignment::class, 'committee_id');
    }

    public function memberReportingRelations(): HasMany
    {
        return $this->hasMany(MemberReportingRelation::class, 'committee_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'committee_id');
    }

    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class, 'committee_id');
    }

    // Future placeholder: committeeMembers()
    // Future placeholder: committeeLeaders()
    // Future placeholder: notices()
    // Future placeholder: events()
}
