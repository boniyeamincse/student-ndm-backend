<?php

namespace App\Models;

use App\Enum\MemberStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'membership_application_id',
        'member_no',
        'full_name',
        'email',
        'mobile',
        'photo',
        'gender',
        'date_of_birth',
        'blood_group',
        'father_name',
        'mother_name',
        'educational_institution',
        'department',
        'academic_year',
        'occupation',
        'address_line',
        'village_area',
        'post_office',
        'union_name',
        'upazila_name',
        'district_name',
        'division_name',
        'emergency_contact_name',
        'emergency_contact_phone',
        'status',
        'bio',
        'last_status_changed_at',
        'status_note',
        'joined_at',
        'approved_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status'                 => MemberStatus::class,
            'date_of_birth'          => 'date',
            'last_status_changed_at' => 'datetime',
            'joined_at'              => 'datetime',
            'approved_at'            => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Member $member) {
            if (empty($member->uuid)) {
                $member->uuid = (string) Str::uuid();
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(MembershipApplication::class, 'membership_application_id');
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
        return $this->hasMany(MemberStatusHistory::class, 'member_id')->latest('created_at');
    }

    public function committeeAssignments(): HasMany
    {
        return $this->hasMany(CommitteeMemberAssignment::class, 'member_id');
    }

    public function profileUpdateRequests(): HasMany
    {
        return $this->hasMany(ProfileUpdateRequest::class, 'member_id');
    }
}
